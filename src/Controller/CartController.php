<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Repository\CartRepository;
use App\Repository\CompanyRepository;
use App\Repository\ProductRepository;
use App\Service\VignoblexportApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cart")
 */
class CartController extends AbstractController
{

    /**
     * @Route("/{id}/customer", name="cart_buyer", methods={"GET"})
     */
    public function buyerCart(CartRepository $cartRepository, $id): Response
    {

        $carts = $cartRepository->findAllByBuyer($id);
        $sellerList = [];
        foreach($carts as $cart) {

            $company = $cart->getProduct()->getCompany();
            if (!in_array($company, $sellerList)) {
                $sellerList[] = $company;
            }
        }


        return $this->render('cart/buyer_cart.html.twig', [
            'carts' => $carts,
            'companies' => $sellerList
        ]);
    }

    /**
     * @Route("/{cartId}/editQuantity/{quantity}", name="edit_cart_quantity")
     */
    public function changeQuantity($cartId, $quantity, CartRepository $cartRepository)
    {
        $cart = $cartRepository->find($cartId);

        if($quantity > 0 && $quantity < $cart->getProduct()->getStockQuantity()){
            $cart->setQuantity($quantity);
            $cart->setTotalAmount($quantity * $cart->getProduct()->getPrice());

            $entityManager = $this->getDoctrine()->getManager();
        
            $entityManager->persist($cart);
            $entityManager->flush();
        }

        return new JsonResponse(Response::HTTP_OK);
    }

    /**
     * @Route("/{productId}/add/{quantity}", name="add_cart", methods={"GET"})
     */
    public function addCart(CartRepository $cartRepository, ProductRepository $productRepository, $productId, $quantity): Response
    {
        if($quantity <= 0) {
            $this->addFlash('danger', "Nan mais tu peux pas acheter $quantity bouteilles, allez hop d??gages de l??");
            return $this->redirectToRoute('product_show_shop', ['id' => $productId]);
        }

        $cart = new Cart();
        // On peut r??cup??rer l'utilisateur courant avec $this->getUser()
        $userId = $this->getUser()->getId();
        $product = $productRepository->find($productId);

        if($quantity > $product->getStockQuantity()) {
            $this->addFlash('danger', "Fais gaffe, t'ach??tes trop de bouteilles l??, tu vas mal finir");
            return $this->redirectToRoute('product_show_shop', ['id' => $productId]);
        } 

        // On v??rifie qu'un cart n'existe pas d??j?? entre l'utilisateur et le produit.
        $result = $cartRepository->findExistingCart($userId, $productId);

        // Si un cart existe d??j?? et que le produit est en vente
        // Alors on r??cup??re le premier (et normalement unique) r??sultat
        if(isset($result[0]) && $product->getStatus() != 0) {

            $cart = $result[0];
            // On ajoute 1 ?? la quantit??
            $cart->setQuantity($cart->getQuantity() + $quantity);
            // Puis on met ?? jour le montant total
            $cart->setTotalAmount($cart->getTotalAmount() + $product->getPrice());
        } else {

            // Si il n'existe pas, on en cr???? un nouveau
            $cart->setUser($this->getUser());
            // On v??rifie au pr??alable que le produit existe bel et bien en BDD et qu'il est en vente
            if(!empty($product) && $product->getStatus() != 0) {

                $cart->setProduct($product);
            } else {
                $this->addFlash("warning","Le produit n'existe pas ou n'est plus en vente.");
                return $this->redirectToRoute('product_list_shop');
            }
            // On initialise la quantit?? du nouveau cart ?? 1
            $cart->setQuantity($quantity);
            // Ainsi que le montant total
            $cart->setTotalAmount($product->getPrice() * $quantity);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($cart);
        $entityManager->flush();
        $this->addFlash("success","Le produit a bien ??t?? ajout?? ?? votre panier !");
        return $this->redirectToRoute('product_show_shop', ['id' => $productId]);
    }

    /**
     * @Route("/{companyId}/details", name="cart_details", methods={"GET"})
     */
    public function details(CompanyRepository $companyRepository, CartRepository $cartRepository, VignoblexportApi $vignoblexportApi, $companyId): Response
    {
        $company = $companyRepository->find($companyId);
        $entityManager = $this->getDoctrine()->getManager();
        $totalQuantity = 0;
        $totalCartAmount = 0;
        // 1)Cr??er un objet Order avec les coordonn??es de l'user
        $order = new Order();
        $order->setStatus(0);
        $order->setCompany($company);
        $order->setBuyer($this->getUser());

        $carts = $cartRepository->findAllByBuyer($this->getUser()->getId());
        foreach($carts as $cart) {

            if($cart->getProduct()->getCompany() == $company) {
                $cartList[] = $cart;
                $totalQuantity += $cart->getQuantity();
                $totalCartAmount += $cart->getTotalAmount();

                // 2)Cr??er des objets OrderProducts pour lier les Products achet??s
                $orderProduct = new OrderProduct();
                $orderProduct->setProduct($cart->getProduct());
                $orderProduct->setQuantity($cart->getQuantity());

                $order->addOrderProduct($orderProduct);
            }
        }

        $order->setTotalQuantity($totalQuantity);
        $order->setTotalAmount($totalCartAmount);
 
        // TODO : Infos g??n??r??es par l'api (plus tard)
        // $order->setTrackingNumber(random_int(10000000, 99999999));
        $order->setCarrier($vignoblexportApi->estimateShippingCosts($order)['name']);
        $order->setShippingCosts($vignoblexportApi->estimateShippingCosts($order)['price']);
        // $order->setReference('???');
        
        return $this->render('cart/details.html.twig', [
            'carts' => $cartList,
            'totalQuantity' => $totalQuantity,
            'totalCartAmount' => $totalCartAmount,
            'company' => $company,
            'order' => $order
        ]);
    }

    /**
     * @Route("/{companyId}/validate", name="cart_validate", methods={"GET"})
     */
    public function cartValidate(CompanyRepository $companyRepository, CartRepository $cartRepository, VignoblexportApi $vignoblexportApi, $companyId) {
        
        $company = $companyRepository->find($companyId);
        $entityManager = $this->getDoctrine()->getManager();
        $totalQuantity = 0;
        $totalCartAmount = 0;
        // 1)Cr??er un objet Order avec les coordonn??es de l'user
        $order = new Order();
        $order->setStatus(2);
        $order->setCompany($company);
        $order->setBuyer($this->getUser());

        // 1.5) R??cup??rer tous les carts de l'user courant qui vont vers le companyId
        $carts = $cartRepository->findAllByBuyer($this->getUser()->getId());
        foreach($carts as $cart) {

            if($cart->getProduct()->getCompany() == $company) {
                $cartList[] = $cart;
                $totalQuantity += $cart->getQuantity();
                $totalCartAmount += $cart->getTotalAmount();

                // 2)Cr??er des objets OrderProducts pour lier les Products achet??s
                $orderProduct = new OrderProduct();
                $orderProduct->setProduct($cart->getProduct());
                $orderProduct->setQuantity($cart->getQuantity());

                $order->addOrderProduct($orderProduct);

                $entityManager->persist($orderProduct);
                // 3)Effacer les carts de l'user qui ont ??t?? ajout??s ?? l'Order
                $entityManager->remove($cart);
                //$entityManager->flush();

            }
        }

        $order->setTotalQuantity($totalQuantity);
        $order->setTotalAmount($totalCartAmount);
 
        // TODO : Infos g??n??r??es par l'api (plus tard)
        // $order->setTrackingNumber(random_int(10000000, 99999999));
        $order->setCarrier($vignoblexportApi->estimateShippingCosts($order)['name']);
        $order->setShippingCosts($vignoblexportApi->estimateShippingCosts($order)['price']);
        $order->setReference('???');

        $entityManager->persist($order);
        $entityManager->flush();

        $this->addFlash("success","Votre commande a bien ??t?? valid??e !");

        return $this->redirectToRoute('cart_buyer',['id' => $this->getUser()->getId()]);

    }

    /**
     * @Route("/{id}", name="cart_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Cart $cart): Response
    {
        if ($this->isCsrfTokenValid('delete'.$cart->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($cart);
            $entityManager->flush();

            $this->addFlash("success","Le produit a bien ??t?? retir?? du panier");
        }

        return $this->redirectToRoute('cart_buyer',['id' => $this->getUser()->getId()]);
    }
}
