var jp = "";
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
		
	$(document).on('click', '.popup-modal-dismiss', function (e) {
		e.preventDefault();
		$.fancybox.close();
	});
	
	function validation() {
		$.validator.addMethod("selectRequired",function(value,element){
			return value != 0;
		}, "El campo es requerido");
		
		$.validator.addMethod("guestNumber",function(value,element){
			return value > 1;
		}, "El valor ingresado en este campo debe ser un número entero mayor que 1.");
		
		$.validator.addMethod("noSpaceStart", function(value, element) { 
		    return value.indexOf(" ") != 0; 
		}, "El campo es requerido");

		$.validator.addMethod("noSpaceEnd", function(value, element) { 
		    return value.lastIndexOf(" ") != value.length - 1; 
		}, "El campo es requerido");
				
		$("#frmSaveList").validate({
			rules:{
				name: {
					required:true,
					noSpaceStart:true,
					noSpaceEnd:true
				},
				event_type: "selectRequired",
				event_date: {
					required:true,
					noSpaceStart:true,
					noSpaceEnd:true
				},
				guest_number: {
					required: true,
					number:true,
					guestNumber:true
				},
				message: {
					maxlength:1000
				},
				city:{
					required:true,
					noSpaceStart:true,
					noSpaceEnd:true
				},
				town:{
					required:true,
					noSpaceStart:true,
					noSpaceEnd:true
				},
				tel:{
					required:true,
					noSpaceStart:true,
					noSpaceEnd:true
				},
				cel:{
					required:true,
					noSpaceStart:true,
					noSpaceEnd:true
				},
				email_cocreator: "email"
			},
			message:{
				required:"El campo es requerido"
			}/*,
			submitHandler: function(form) {
				$(form).submit();
			}*/
		});	
	}
	
	
	//eliminar
	$(".delete-list").on('click', function(e){
        var id = $(this).val();
		e.preventDefault();
        $.fancybox({
             type: 'inline',
             autoScale: true,
             minHeight: 100,
             minWidth: 180,
             'transitionIn': 'elastic',
             'transitionOut': 'elastic',
             'speedIn': 500,
             'speedOut': 300,
             'centerOnScroll': true,
             'content' : '<div class="ax-popup-delete"><p>¿Estás seguro que deseas eliminar esta lista?</p><a href="#" id="cancel"  class="cancel btn btn-default btn-lista-regalos">Cancelar</a><a href="#" id="acept" class="acept btn btn-default btn-lista-regalos">Aceptar</a></div>'
        });
        $("#cancel").on('click',function(){
            $.fancybox.close();
        });
        $("#acept").on('click',function(){
             $.ajax({
                url: $(".actions").attr("action"),
                type: 'POST',
                data: {
                    ajax: true,
                    method: "delete",
                    id_list: id
                },
                headers: { "cache-control": "no-cache" },
                success: function(result){
                    result = JSON.parse(result);
                    $.fancybox({
                         'autoScale': true,
                         'transitionIn': 'elastic',
                         'transitionOut': 'elastic',
                         'minHeight': 30,
                         'minWidth': 240,
                         'speedIn': 500,
                         'speedOut': 300,
                         'centerOnScroll': true,
                         'content' : $('<p>').text(result.msg)
                    });
                    jp.jplist({
                      command: 'del'
                      ,commandData: {
                         $item:  $("#list-"+result.id)
                      }
                   });
                }
            });   
        });
	});
	
	//compartir
	 
	setTimeout(function() {
		$("#closeMsg").parent().remove();
	}, 8000);
    
        if($(window).width() <= 568) {
            tabRpListAdmin()
        }else {
            $('.tab-rp-listas-rega').next().show();
        }

    function tabRpListAdmin(){
        $('.tab-rp-listas-rega').next().hide();

        $('.tab-rp-listas-rega').on('click', function(){
            $(this).next().slideToggle();
            $(this).toggleClass('active-tab-list');
        })
    }
    
   jp = $('#lists').jplist({				
      itemsBox: '.ax-cont-admin-listas-regalos' 
      ,itemPath: '.list-item-container' 
      ,panelPath: '.jplist-panel'	
   });
    
  $('.jplist-pagingmid').after($('.jplist-label'));
    
});

