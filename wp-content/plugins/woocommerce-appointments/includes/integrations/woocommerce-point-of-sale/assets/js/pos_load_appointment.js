jQuery(document).ready(function ($) {

	// Load order of appointment by order id
	function loadappointment() {
	   // Get Order ID
	   var order_id = parseInt(localStorage.getItem('pos_load_appointmentorder'));

	   if(order_id === null){
		   order_id = parseInt(window.location.href.split('#').pop());
	   }

	   if(order_id > 0){

		   document.location.hash = '#loaded';

		   // Get list of orders - serach for order by id
			APP.getOrdersListContent({count: 100, currentpage: 1, reg_id: 'all', search:order_id });
			//$('#retrieve_sales').trigger('click');

			// Load Order
			setTimeout(function(){
				APP.loadOrder(order_id);
				console.log('Order loaded: #' + order_id);
				//closeModal('modal-retrieve_sales');

				// Reset
				localStorage.removeItem('pos_load_appointmentorder');
				order_id = null;
			}, 700);

		}
	   return false;
   }

   // Prevent opening another window if POS is already loaded
   function set_hash_on_load() {
	   document.location.hash = '#loaded';
   }

   if( $('body').hasClass('wc_poin_of_sale_body') ){

	   set_hash_on_load();

	   // Load Order when URL changes
	   $(window).on('hashchange', function(e) {
		   if( /^#\d+$/.test(document.location.hash) && document.location.hash != '#loaded'  ){
			   loadappointment();
		   }
	   });

	   // Load Order on Page Load
	   setTimeout(function() {
		   APP.getOrdersListContent({count: 100, currentpage: 1, reg_id: 'all'});
		   if( /^#\d+$/.test(document.location.hash) || document.location.hash == '#loaded'){
				loadappointment();
			}
	   }, 3000);
   }

});
