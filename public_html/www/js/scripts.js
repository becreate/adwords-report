$(document).ready(function(){
    
    //automaticke schování chybové zprávy
    $('#flashes.alert').fadeTo(3500, 500).slideUp(500, function(){
       $('#flashes.alert').slideUp(500); 
    });

});