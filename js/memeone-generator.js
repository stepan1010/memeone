var canvas = document.getElementById("memeone_canvas");

function memeone_load_image() {
    
    // First, we make our loading icon visible.
    var loading_icon = document.getElementById('memeone_loading_icon');
    loading_icon.style.display = "inline";

    var canvas = document.getElementById("memeone_canvas");
    var context = canvas.getContext("2d");
    canvas.style.display = "none";

    var input, file, fr, img;

    // Then we check if FileReader is supported
    if (typeof window.FileReader !== 'function') {
        memeone_print("Hey, Your browser seems out of date.");
        return;
    }

    // After that we check if there is a file input
    input = document.getElementById('memeone_imgfile');
    if (!input) {
        // If an error is encountered we hide the loading icon and print an appropriate error message
       loading_icon.style.display = "none";
        memeone_print("Um, it seems that there is no file input element.");
    }

    // We then check if browser supports file inputs
    else if (!input.files) {
        loading_icon.style.display = "none";
        memeone_print("Hey, Your browser seems out of date.");
    }

    // And finally we check whether the file has been selected
    else if (!input.files[0]) {
       loading_icon.style.display = "none";
        memeone_print("Please select a file first.");
    } 

    else if (!input.files[0].type.match('image.*')) {
       loading_icon.style.display = "none";
        memeone_print("This is not am image. Please select an image file.");

        var div = document.getElementById('memeone_meme_placeholder');

        // Remove all previous children before appending (in case the user uploaded image and then uploaded a non-image)
        while (div.firstChild) {
            div.removeChild(div.firstChild);
        }

        // Make form invisbile (in case it is)
        document.getElementById('memeone_generator_form').style.display = "none";
    } 
    // If everything is in place, we proceed further
    else {

        // First, hide our error area (in case there were any errors prior to this point)
        document.getElementById('memeone_error_message_area').style.display = "none";

        file = input.files[0];
        // Invoke the FileReader.
        fr = new FileReader();
        // Create a new image onload
        fr.onload = memeone_create_image;
        fr.readAsDataURL(file);
    }

    // Pass created image to the FileReader
    function memeone_create_image() {
        img = new Image();
        img.onload = memeone_image_loaded;
        img.src = fr.result;
    }

    // Once our file is loaded we start to create a image out of it.
    function memeone_image_loaded() {
    	//var maintext_font_size = parseInt(document.getElementById("memeone_top_text_font_size").value);

        // First, calculate dimensions for our future image
        //memeone_calculate_canvas_dimensions(function(){
        canvas.style.position = 'inherit';
        canvas.width = img.width;
        canvas.height = img.height;

           // var picture_x = canvas.width / 2 - (img.width / 2);
            // Then draw background
           // memeone_draw_frame(picture_x, function(){
               /// And then draw the picture on top of it
                memeone_draw_picture(function(){

                    // After we are done, show the canvas and show the form for text inputs.
                    canvas.style.display = "block";
                    document.getElementById('memeone_generator_form').style.display = "block";
                    // Also we got to hide that loading icon
                   loading_icon.style.display = "none";

                });
          //  });    
        }

        function memeone_draw_picture(callback) {

            /* Finally we draw a picture that user has uploaded. Since we already know all the coordinates
            *   we can start drawing right away.
            */
            var context = canvas.getContext("2d");
            context.drawImage(img, 0, 0);

            // Now we need to place our image to the placeholder to use it everytime we need to redraw it.
            var image = new Image();
            image.id = "memeone_background_picture";
            image.src = canvas.toDataURL();
            image.style.display = "none";
            var div = document.getElementById('memeone_meme_placeholder');

            // Remove all previous children before appending (just in case the user pressed "Upload" button twice or change font)
            while (div.firstChild) {
                div.removeChild(div.firstChild);
            }

            div.insertBefore(image, div.firstChild);

            callback();
        }
    }

    // This function is called everytime our application would like to print an error message. 
    function memeone_print(msg) {

        var error_area = document.getElementById('memeone_error_message_area');

        // First, remove all previous errors. 
        while (error_area.firstChild) {
            error_area.removeChild(error_area.firstChild);
        }

        // Create a new paragraph with desired error message as contents
        var p = document.createElement('p');
        p.innerHTML = msg;

        // Append paragraph to the error area
        error_area.appendChild(p);

        // Make error area visible
        error_area.style.display = "block";
    }
    
function memeone_type_text(){

	var canvas = document.getElementById("memeone_canvas");
	var context = canvas.getContext("2d");
	var background = document.getElementById("memeone_background_picture");

    var top_text_font_size = parseInt(document.getElementById("memeone_top_text_font_size").value);
    var bottom_text_font_size = parseInt(document.getElementById("memeone_bottom_text_font_size").value);

	memeone_clear_canvas(canvas, context, function(context){
		memeone_draw_background(context, background, function(context){
			memeone_type_top_text(canvas, context, background, top_text_font_size, function(context){
				memeone_type_bottom_text(canvas, context, background, bottom_text_font_size);
			});		
		});
	});	
}

function memeone_preload_image_to_canvas(){
    var canvas = document.getElementById("memeone_canvas");
    var context = canvas.getContext("2d");
    var background = document.getElementById("memeone_background_picture");
    console.log(background.width);
    console.log(background.height);

    canvas.width = background.width;
    canvas.height = background.height;

    memeone_draw_background(context, background, function(){
        canvas.style.display = 'block';
        canvas.style.position = 'inherit';
        background.style.display = 'none';
    });
}

function memeone_clear_canvas(canvas, context, cb){
	context.clearRect(0, 0, canvas.width, canvas.height);
	cb(context);
}

function memeone_draw_background(context, background, cb) {
	context.drawImage(background, 0, 0);
	cb(context);
}

function memeone_type_top_text(canvas, context, background, fontsize, cb) {
	
	var top_text = document.getElementById("memeone_meme_top_text");

	background = background.width;
	var spaceBetweenLines = fontsize + (fontsize / 10);
	var maxWidth = background - Math.round(background / 8);

	var strokeWidth = fontsize / 15;

	context.font = fontsize.toString() + 'px MemeoneFont';
	memeone_wrap_text(context, top_text.value.toUpperCase(), canvas.width/2, fontsize + (fontsize/6), maxWidth, spaceBetweenLines, strokeWidth, fontsize);
	cb(context);
}

function memeone_type_bottom_text(canvas, context, background, fontsize) {
	
	var top_text = document.getElementById("memeone_meme_bottom_text");

	background = background.width;
	var spaceBetweenLines = fontsize + (fontsize / 10);
	var maxWidth = background - Math.round(background / 8);

    var strokeWidth = fontsize / 15;
	
	var metrics = context.measureText(top_text.value.toUpperCase());
	var numberOfLines = metrics.width / maxWidth;

	context.font = fontsize.toString() + 'px MemeoneFont';
	
	memeone_count_lines(context, top_text.value.toUpperCase(), maxWidth, function (numberOfLines) {
      memeone_wrap_text(context, top_text.value.toUpperCase(), canvas.width/2, (canvas.height - fontsize/3) - (spaceBetweenLines * numberOfLines), maxWidth, spaceBetweenLines, strokeWidth, fontsize);	
    });

	//memeone_wrap_text(context, top_text.value.toUpperCase(), canvas.width/2, canvas.height - (fontsize * Math.ceil(numberOfLines)), maxWidth, spaceBetweenLines, strokeWidth, fontsize);
}

function memeone_count_lines(context, text, maxWidth, cb){
    var words = text.split(' ');
    var lineCounter = 1;
    var line = '';

    for(var n = 0; n < words.length; n++) {
      var testLine = line + words[n] + ' ';
      var metrics = context.measureText(testLine);
      var testWidth = metrics.width;
      if (testWidth > maxWidth && n > 0) {
        line = words[n] + ' '; 
        lineCounter += 1;
      }
      else {
        line = testLine;
      }
    }
    cb(lineCounter - 1);     
}

function memeone_wrap_text(context, text, x, y, maxWidth, lineHeight, strokeWidth, fontsize) {
	    var words = text.split(' ');
        var line = '';

		context.textAlign = 'center';
		context.fillStyle = 'white';

        for(var n = 0; n < words.length; n++) {
          var testLine = line + words[n] + ' ';
          var metrics = context.measureText(testLine);
          var testWidth = metrics.width;

          if (testWidth > maxWidth && n > 0) {
		    context.lineWidth = strokeWidth;
        var strokeCoords = strokeWidth - 2;
		    context.strokeText(line, x+strokeCoords, y-strokeCoords);
		    context.strokeText(line, x+strokeCoords, y);
		    context.strokeText(line, x-strokeCoords, y);
		    context.strokeText(line, x-strokeCoords, y+strokeCoords);
		    context.strokeText(line, x-strokeCoords, y-strokeCoords);
		    context.strokeText(line, x+strokeCoords, y+strokeCoords);
		    context.lineWidth = 1;
		    context.fillText(line, x, y);
            line = words[n] + ' ';
            y += lineHeight;
          }
          else {
            line = testLine;

          }
        }
        context.lineWidth = strokeWidth;
         var strokeCoords = strokeWidth - 2;
        context.strokeText(line, x+strokeCoords, y);
		context.strokeText(line, x-strokeCoords, y);
        context.strokeText(line, x+strokeCoords, y-strokeCoords);
        context.strokeText(line, x-strokeCoords, y+strokeCoords);
        context.strokeText(line, x-strokeCoords, y-strokeCoords);
        context.strokeText(line, x+strokeCoords, y+strokeCoords);
        context.lineWidth = 1;
        context.fillText(line, x, y);
}

function memeone_submit_meme() {
    var top_text = document.getElementById("memeone_meme_top_text").value;
    var bottom_text = document.getElementById("memeone_meme_bottom_text").value;

    // Do not submit if mainline is empty
    if (top_text.trim().length == 0 && bottom_text.trim().length == 0){

        memeone_print("Please enter text first.");
        
    } else {

        // Move the meme from canvas to our hidden input tag
        document.getElementById("memeone_created_meme").value = document.getElementById("memeone_canvas").toDataURL("image/jpeg");
        // Submit the form.
        document.forms["memeone_generator_form"].submit();
    }
}