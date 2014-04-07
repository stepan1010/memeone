/*  The way this whole thing works is as follows:
*   1) User can choose whether to use one of existing background or to upload his own
*       a) In case he selects an already existing one - we go to step 2.
*       b) If the user wants to upload his own picture we create a special form for that and after the user clicks "Upload" we go to step 2
*   2) We start processing his picture.
*       We create a canvas, considering picture's dimensions.
*       Draw our the background picture on it.
*       And store it in an invisible div element.
*   4) We display the form for top and bottom texts.
*   5) When the user starts typing, we must draw each letter he types on the fly.
*       So we need to redraw everything we have on our canvas and draw a letter.
*       We can't just draw on top of the canvas without emptying it everytime because then removing a letter would be impossible.
*       That's why we have saved our background image to an invisible div element.
*       So now, we can use it to redraw background and type text on top of it.
*   6) Once the user is done, he submits the picture (as base64 encoded string), both texts and a background name (if available) to the server.
*/

// This function is triggered after the user hit "Upload" button (in case he chose to upload his own picture)
function memeone_load_image() {
    
    // First, we make our loading icon visible.
    var loading_icon = document.getElementById('memeone_loading_icon');
    loading_icon.style.display = "inline";

    // Get the context to draw on
    var canvas = document.getElementById("memeone_canvas");
    var context = canvas.getContext("2d");
    
    // Hide the canvas until the picture is loaded onto it  
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
    	
        // Canvas dimensions should be the same as image's
        canvas.width = img.width;
        canvas.height = img.height;

        /// And then draw the picture
        memeone_draw_picture(function(){

            // After we are done, show the canvas and show the form for text inputs.
            canvas.style.position = 'inherit';
            canvas.style.display = "block";
            document.getElementById('memeone_generator_form').style.display = "block";

            // Also we got to hide that loading icon
            loading_icon.style.display = "none";
        });
  
    }

    function memeone_draw_picture(callback) {

        // Finally we draw a picture that user has uploaded.
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

        // Append the image to the placeholder
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
    
// This function is called everytime the user inputs a letter
function memeone_type_text(){

	var canvas = document.getElementById("memeone_canvas");
	var context = canvas.getContext("2d");

    // Get our hidden background image
	var background = document.getElementById("memeone_background_picture");

    // Get texts' font sizes
    var top_text_font_size = parseInt(document.getElementById("memeone_top_text_font_size").value);
    var bottom_text_font_size = parseInt(document.getElementById("memeone_bottom_text_font_size").value);

    // Clear the canvas first
	memeone_clear_canvas(canvas, context, function(context){

        // Then draw the background
		memeone_draw_background(context, background, function(context){

            // Then draw top text
			memeone_type_top_text(canvas, context, background, top_text_font_size, function(context){

                // And finally draw bottom text
				memeone_type_bottom_text(canvas, context, background, bottom_text_font_size);
			});		
		});
	});	
}

/* This function is called when the user selects the background from already existing ones.
*   It has similar functionality as memeone_draw_picture() but here we don't need to append our background
*   to the hidden placeholder (it is already appended we just need to draw it on the canvas)
*/
function memeone_preload_image_to_canvas(){
    var canvas = document.getElementById("memeone_canvas");
    var context = canvas.getContext("2d");
    var background = document.getElementById("memeone_background_picture");

    // Set canvas dimensions
    canvas.width = background.width;
    canvas.height = background.height;

    // Draw the background
    memeone_draw_background(context, background, function(){

        // Once done, make canvas visible and background hidden
        canvas.style.display = 'block';
        canvas.style.position = 'inherit';
        background.style.display = 'none';
    });
}

// This function clears the canvas
function memeone_clear_canvas(canvas, context, cb){
	context.clearRect(0, 0, canvas.width, canvas.height);
	cb(context);
}

// This functions draws a given image on the canvas
function memeone_draw_background(context, background, cb) {
	context.drawImage(background, 0, 0);
	cb(context);
}

// This function is responsible for drawing top text.
function memeone_type_top_text(canvas, context, background, fontsize, cb) {
	
    // Get user input
	var top_text = document.getElementById("memeone_meme_top_text");

    // Now we have some calculations to do
	background = background.width;
	var spaceBetweenLines = fontsize + (fontsize / 10);
	var maxWidth = background - Math.round(background / 8);
	var strokeWidth = fontsize / 15;

	context.font = fontsize.toString() + 'px MemeoneFont';

    // After we are done, we call the wrapping function which does all the line breaking, word wrapping and drawing.
	memeone_wrap_text(context, top_text.value.toUpperCase(), canvas.width/2, fontsize + (fontsize/6), maxWidth, spaceBetweenLines, strokeWidth, fontsize);

	cb(context);
}

// This function is responsible for drawing bottom text.
function memeone_type_bottom_text(canvas, context, background, fontsize) {
	
    // Get user input
	var bottom_text = document.getElementById("memeone_meme_bottom_text");

	background = background.width;
	var spaceBetweenLines = fontsize + (fontsize / 10);
	var maxWidth = background - Math.round(background / 8);

    var strokeWidth = fontsize / 15;
	
	var metrics = context.measureText(bottom_text.value.toUpperCase());
	var numberOfLines = metrics.width / maxWidth;

	context.font = fontsize.toString() + 'px MemeoneFont';
	
    /* Drawing bottom text is the same as drawing top text, we just use different coordinates to start from.
    * In order to start at the right place, we need to know how many lines of text do we currently have (to determing Y coordinate)
    */
	memeone_count_lines(context, bottom_text.value.toUpperCase(), maxWidth, function (numberOfLines) {
      memeone_wrap_text(context, bottom_text.value.toUpperCase(), canvas.width/2, (canvas.height - fontsize/3) - (spaceBetweenLines * numberOfLines), maxWidth, spaceBetweenLines, strokeWidth, fontsize);	
    });
}

// This function is used to determine how many lines of text do we currently have.
function memeone_count_lines(context, text, maxWidth, cb){

    // Get text by words
    var words = text.split(' ');
    var lineCounter = 1;
    var line = '';

    // Add each word to the line, until we reach the max width. Once we reach max width we know it's a linebreak
    for(var n = 0; n < words.length; n++) {
        var testLine = line + words[n] + ' ';
        var metrics = context.measureText(testLine);
        var testWidth = metrics.width;

        if (testWidth > maxWidth && n > 0) {
            line = words[n] + ' '; 
            lineCounter += 1;
        } else {
            line = testLine;
        }
    }

    cb(lineCounter - 1);     
}

// This function is responsible for all the line breaking, word wrapping and actual drawing.
function memeone_wrap_text(context, text, x, y, maxWidth, lineHeight, strokeWidth, fontsize) {

    // Get the text by words
    var words = text.split(' ');
    var line = '';

    // Assign style to our text
	context.textAlign = 'center';
	context.fillStyle = 'white';

    // Add each word to the line, until we reach the max width. Once we reach max width we know it's a linebreak
    for(var n = 0; n < words.length; n++) {
        var testLine = line + words[n] + ' ';
        var metrics = context.measureText(testLine);
        var testWidth = metrics.width;

        // If there is a linebreak needed, then draw the line and start a new one
        if (testWidth > maxWidth && n > 0) {

            // Doing some sweet stroking
            context.lineWidth = strokeWidth;
            var strokeCoords = strokeWidth - 2;
            context.strokeText(line, x+strokeCoords, y-strokeCoords);
    	    context.strokeText(line, x+strokeCoords, y);
    	    context.strokeText(line, x-strokeCoords, y);
    	    context.strokeText(line, x-strokeCoords, y+strokeCoords);
    	    context.strokeText(line, x-strokeCoords, y-strokeCoords);
    	    context.strokeText(line, x+strokeCoords, y+strokeCoords);
    	    context.lineWidth = 1;

            // Finally draw the text on top of stroke
    	    context.fillText(line, x, y);
            line = words[n] + ' ';

            // Adjust drawing coordinates
            y += lineHeight;
        } else {

            // If no line break needed, save the text for later drawing
            line = testLine;
        }
    }

    // Again sweet stroking
    context.lineWidth = strokeWidth;
    var strokeCoords = strokeWidth - 2;
    context.strokeText(line, x+strokeCoords, y);
    context.strokeText(line, x-strokeCoords, y);
    context.strokeText(line, x+strokeCoords, y-strokeCoords);
    context.strokeText(line, x-strokeCoords, y+strokeCoords);
    context.strokeText(line, x-strokeCoords, y-strokeCoords);
    context.strokeText(line, x+strokeCoords, y+strokeCoords);
    context.lineWidth = 1;

    // Finally draw the line
    context.fillText(line, x, y);   
}

// This function is called when the user is done with the meme and wants to submit it
function memeone_submit_meme() {

    // Get his inputs
    var top_text = document.getElementById("memeone_meme_top_text").value;
    var bottom_text = document.getElementById("memeone_meme_bottom_text").value;

    // Do not submit if both inputs are empty
    if (top_text.trim().length == 0 && bottom_text.trim().length == 0){

        memeone_print("Please enter text first.");
        
    } else {

        // Move the meme from canvas to our hidden input tag
        document.getElementById("memeone_created_meme").value = document.getElementById("memeone_canvas").toDataURL("image/jpeg");

        // Submit the form.
        document.forms["memeone_generator_form"].submit();
    }
}