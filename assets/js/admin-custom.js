jQuery( document ).ready(function() {
    
  jQuery( ".woocommerce-validate-current-status" ).click(function() {
 var tra_id=jQuery(this).attr('id');
 
  var currentelement=jQuery(this);


   jQuery.ajax({
    type: "POST",
    url: ajaxurl,
    data: { action: 'check_transaction_status' , tra_id: tra_id }
  }).done(function( msg ) {
     
     currentelement.after(msg);
 });
 
 
  });
});