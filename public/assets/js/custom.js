/**
 *
 * You can write your JS code here, DO NOT touch the default style file
 * because it will make it harder for you to update.
 * 
 */

"use strict";

$(document).ready(function () {
    $('#modal-video').on('hidden.bs.modal', function (e) {
        $(".videodiv video")[0].pause(); 
        // $('#post_video').html('<source src="" type="video/mp4"></source>' );
        // $("#post_video video")[0].load();
    });

    $(document).on("click", "#playvideomdl", function() {
        var src = $(this).attr('data-src');
        $('.videodiv video source').attr('src',src );
        $(".videodiv video")[0].load();
        // $(".videodiv video")[0].play(); 
    });
});