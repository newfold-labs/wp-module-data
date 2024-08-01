function waitForElement(selector) {
    return new Promise((resolve, reject) => {
        const element = document.querySelector(selector);
        if (element) {
            resolve(element);
            return;
        }

        const observer = new MutationObserver((mutations, observer) => {
            const element = document.querySelector(selector);
            if (element) {
                resolve(element);
                observer.disconnect();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
}


document.addEventListener('DOMContentLoaded', function() {
    
    waitForElement('#manage_nfd_slug_wonder_cart').then(element => {
        element.addEventListener('click', function(){
            jQuery(document).ready(function($) {

                    $.ajax({
                        url: myAjax.ajaxurl,
                        type: 'GET',
                        data: {
                            action: 'store_page_button_click',
                            clicked: true,
                            label: 'manage_nfd_slug_wonder_cart_clicked',
                            provider: 'yith_wondercart',
                            url: window.location.href.split("#")[0]+'#/store/sales_discounts',
                            eventName: 'ecommerce_exclusive_tools_settings_clicked'
                        },
                        success: function(response) {
                            console.log('Button clicked!'+response);
                        }
                    });
                
            });

        })
    });

    waitForElement('#recent-activity-report-wrapper .nfd-link').then(element => {
        element.addEventListener('click', function(){            
            jQuery(document).ready(function($) {
                $.ajax({
                    url: myAjax.ajaxurl,
                    type: 'GET',
                    data: {
                        action: 'store_page_button_click',
                        clicked: true,
                        label: 'view_analytics_clicked',
                        provider: 'woocommerce',
                        url: element.href,
                        eventName: 'woocommerce_analytics_clicked'
                    },
                    success: function(response) {
                        console.log('Link clicked!'+response);
                    }
                });
                
            });

        })
    });

});