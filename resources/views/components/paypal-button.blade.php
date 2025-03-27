<div>
    <div id="paypal-button-container-{{ $planId }}"></div>
    <script>
        paypal.Buttons({
            createSubscription: function(data, actions) {
                return actions.subscription.create({
                    'plan_id': '{{ $planId }}'
                });
            },
            onApprove: function(data, actions) {
                console.log(data);
                console.log(actions);
                console.log('You have successfully subscribed to ' + data.subscriptionID);
            }
        }).render('#paypal-button-container-{{ $planId }}'); // Renders the PayPal button
    </script>
</div>
