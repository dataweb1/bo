(function ($) {

  function initYoutubeApi() {
    var youtubeScriptId = 'youtube-api';
    var youtubeScript = document.getElementById(youtubeScriptId);

    if (youtubeScript === null) {
        var tag = document.createElement('script');
        var firstScript = document.getElementsByTagName('script')[0];

        tag.src = 'https://www.youtube.com/iframe_api';
        tag.id = youtubeScriptId;
        firstScript.parentNode.insertBefore(tag, firstScript);
    }
  }

  function onPlayerStateChange(event) {
    if(!window.playing){
      window.playing = true;
      $(".carousel").carousel('pause');
    }
    else {
      window.playing = false;
      $(".carousel").carousel('cycle');
    }
  }

  Drupal.bo = Drupal.remote_video || {};

  Drupal.behaviors.remote_video = {
    attach: function (context) {

      let slider_player = [];
      let single_player = [];
      initYoutubeApi();

      window.onYouTubeIframeAPIReady = function () {
        $('.carousel').on('slide.bs.carousel', function (e) {
          let prev = $(this).find('.active').index();
          let next = $(e.relatedTarget).index();

          $(this).find(".slider-video").each(function (index) {
            let slide_video = $(this)[0];
            let video_id = slide_video.getAttribute('data-video-id');
            let videoSlide = $(this).closest('.carousel-item').index();
            if ($(slide_video).hasClass("youtube-video")) {
              if (next === videoSlide) {
                if (slide_video.tagName === 'IFRAME') {
                  slider_player[video_id].playVideo();
                } else {
                  slider_player[video_id] = new window.YT.Player(slide_video, {
                    videoId: video_id,
                    playerVars: {
                      autoplay: 1,
                      modestbranding: 1,
                      controls: 0,
                      fs: 0,
                      iv_load_policy: 3,
                      cc_load_policy: 0,
                      rel: 0
                    },
                    events: {
                      'onStateChange': onPlayerStateChange
                    }
                  });

                }
              } else {
                if (typeof slider_player[video_id] !== 'undefined') {
                  slider_player[video_id].pauseVideo();
                }
              }
            }

            if ($(slide_video).hasClass("vimeo-video")) {
              if (next === videoSlide) {
                var iframe = $(slide_video).find("iframe")[0];
                slider_player[video_id] = Froogaloop(iframe);
                slider_player[video_id].api('play');

                // When the player is ready, add listeners for finish, and playProgress
                slider_player[video_id].addEvent('ready', function () {
                  slider_player[video_id].addEvent('finish', onFinish);
                  slider_player[video_id].addEvent('playProgress', onPlayProgress);
                  slider_player[video_id].addEvent('pause', onPause);
                });

                function onFinish(id) {
                  $('.carousel').carousel('cycle');
                }

                function onPlayProgress(data, id) {
                  $('.carousel').carousel('pause');
                }

                function onPause(id) {
                  $('.carousel').carousel('cycle');
                }
              }
              else {
                if (typeof slider_player[video_id] !== 'undefined') {
                    slider_player[video_id].api('pause');
                }
              }
            }
          });
        });

        $(".single-video").each(function (index) {
          let single_video = $(this)[0];
          let video_id = single_video.getAttribute('data-video-id');

          if ($(single_video).hasClass("youtube-video")) {
            single_player[video_id] = new window.YT.Player(single_video, {
              videoId: video_id,
              playerVars: {
                autoplay: 0,
                modestbranding: 1,
                controls: 1,
                fs: 0,
                iv_load_policy: 3,
                cc_load_policy: 0,
                rel: 0
              },
              events: { }
            });
          }

          if ($(single_video).hasClass("vimeo-video")) {
            let iframe = $(single_video).find("iframe")[0];
            single_player[video_id] = Froogaloop(iframe);
            single_player[video_id].api('play');
          }
        });
      }
    }
  };

})(jQuery);
