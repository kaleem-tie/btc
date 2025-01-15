
			var pathname = window.location.pathname;
	var origin   = window.location.origin; 
	var subpath = pathname.split('/');
	var urlFile =  origin+'/'+subpath[1];
//alert(urlFile);
	
	
	 /*   $(document).ready(function(){
               $("#stock_id").select2({
                ajax: {
                    url: urlFile+"/ajax_dropdown/getData.php",
                   // type: "post",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
						
                        return {
                            q: params.term // search term
                        };
				},
		 processResults: function (response) {
					// console.log(response);
						//alert(JSON.stringify(response));
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });
		  });*/
	