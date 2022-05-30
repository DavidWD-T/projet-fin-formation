let appShop = {

    init: function(){
        console.log("BIP BOUP, SCRIPT INITIALISÉ");
        var radioBtn = document.querySelectorAll('.priceFilters');
        let radioLen = radioBtn.length;

        var checkboxBtn = document.querySelectorAll("[type='checkbox']");
        let checkboxLen = checkboxBtn.length;

        // assign function to onclick property of each radio button
        for (var i = 0; i < radioLen; i++) {
            radioBtn[i].addEventListener('change', appShop.isClicked);
        }
        for (var i = 0; i < checkboxLen; i++) {
            checkboxBtn[i].addEventListener('change', appShop.isClicked);
        }

    },

    isClicked: function(event) {
        // console.log(event);
        // console.log(event.target.type);
        if(event.target.type === "radio"){
            let checkedPrice = event.currentTarget;
            console.log("TU AS CLIQUÉ SUR UN BOUTON RADIO, AAAAAAAAAH");
            console.log(checkedPrice.value);
            let productCards = document.querySelectorAll('.product-card');
            let productPrice = 0;
            let len = productCards.length;
            for (var i = 0; i < len; i++) {
                productCardPrice = productCards[i].querySelector('.product-card__price')
                console.log(productCardPrice)
                productPrice = parseFloat(productCardPrice.innerText);
                console.log(productPrice);
                if(productPrice > checkedPrice.value && checkedPrice.value != 0) {
                    productCards[i].setAttribute("hidden", "hidden");
                } else {
                    productCards[i].removeAttribute("hidden", "hidden");
                }
            }
        }else if(event.target.type === "checkbox"){
            let checkedData = event.target;
            console.log("TU AS CLIQUÉ SUR UN BOUTON CHECKBOX, AAAAAAAAAH");
            // console.log(event)
            // console.log(event.target)
            // console.log(event.target.checked)
            console.log(checkedData.value)
            let productCards = document.querySelectorAll('.product-card');
            console.log(productCards)
            console.log(event.target.checked)
            let len = productCards.length;
            let querySelectorString = checkedData.className.replace('Filters', '')
            if (event.target.checked === true){
                for (var i = 0; i < len; i++) {
                    productCardData = productCards[i].querySelector('.'+ querySelectorString).querySelector("a").innerText
                    if(productCardData == checkedData.value){
                        productCards[i].removeAttribute("hidden", "hidden");
                    }
                }
            }else if(event.target.checked === false){
                for (var i = 0; i < len; i++) {
                    productCardData = productCards[i].querySelector('.'+ querySelectorString).querySelector("a").innerText
                    if(productCardData == checkedData.value){
                        productCards[i].setAttribute("hidden", "hidden");
                    }
                }
            }
        }
        
    }
}


document.addEventListener('DOMContentLoaded', appShop.init);