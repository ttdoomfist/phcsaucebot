var page  = require('webpage').create(), 
    testindex = 0, 
    loadInProgress = false,
    system     = require('system'),
    response   = {},
    debug      = [],
    logs       = [],
    procedure  = {};
//console.log('test');
page.viewportSize = {
  width: 1200,
  height: 600
};

var args = system.args;
console.log(args);
if (args.length === 1) {
  //console.log('Try to pass some arguments when invoking this script!');
} else {
    var searchstring = args[1];
}

page.settings.userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';
page.settings.javascriptEnabled = true;
page.settings.loadImages = false;//Script is much faster with this field set to false
phantom.cookiesEnabled = true;
phantom.javascriptEnabled = true;
//console.log('All settings loaded, start with execution');
page.onConsoleMessage = function(msg) {
    //console.log(msg);
};

page.onConsoleMessage = function(msg) {
  console.log(msg);
};

page.onLoadStarted = function() {
  loadInProgress = true;
  //console.log("load started");
};
page.onLoadFinished = function() {
  loadInProgress = false;
};
page.onResourceReceived = function (r) {
response = r;
};
page.includeJs("http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js", function() {});
var steps = [
  function() {
	 page.open ('https://duckduckgo.com', function (status) {
		 response.content = status;
          //console.log('Status: ' + status);
            //console.log('URL: ' + window.location.href);

            var waitStart = new Date();
            var done = false;
            var timeNow;
            var interval = 5000;
            while (!done) {
                timeNow = new Date();
                done = timeNow - waitStart > interval;
                //console.log('timeNow - waitStart = ' + (timeNow - waitStart));
            }
	});
  },
  function() {
  
    page.evaluate(function(args){
          console.log(args[1]);
          document.getElementById("search_form_input_homepage").value=args[1]+" !safeoff";
            $('#search_button_homepage').click(function() {
                console.log("I am now clicking");
            });
            $('#search_button_homepage').trigger('click');
            $('#search_form_homepage').submit();

            $("search_form_input_homepage").focus();
    }, args);
  },
  
  function() {
      page.sendEvent('keypress', page.event.key.Enter); 
       page.evaluate(function() {
            var waitStart = new Date();
            var done = false;
            var timeNow;
            var interval = 5000;
            while (!done) {
                timeNow = new Date();
                done = timeNow - waitStart > interval;
                //console.log('timeNow - waitStart = ' + (timeNow - waitStart));
            }
            element = document.getElementById("search_button_homepage");
            $('#search_button_homepage').click();
            $('#search_button_homepage').trigger('click');
            $('#search_form_homepage').submit();
            //console.log(element);
            //console.log($('#search_button_homepage').length);
        });
  },
  
  function() {
        page.evaluate(function() {
            var waitStart = new Date();
            var done = false;
            var timeNow;
            var interval = 5000;
            while (!done) {
                timeNow = new Date();
                done = timeNow - waitStart > interval;
                //console.log('timeNow - waitStart = ' + (timeNow - waitStart));
            }
            
           var resultEl = $('.results .result__a');
           console.log(resultEl.length);
           url = "";
           $.each(resultEl, function(i,v) {
               var posUrl = $(this).attr('href');
               console.log(posUrl);
               if (posUrl.indexOf("view_video.php") >= 0 
                       && posUrl.indexOf("https://") >= 0
                       && posUrl.indexOf("pornhub.com") >= 0) {
                   url = posUrl;
                   return false;
               }
           });
           var urlOld = resultEl.first().attr('href');
           console.log(urlOld);
           console.log(url);
//           console.log(JSON.stringify(url));
        });
  }
];

interval = setInterval(function() {
  if (!loadInProgress && typeof steps[testindex] == "function") {
    //console.log("step " + (testindex + 1));
    
    ret = steps[testindex]();
    page.viewportSize = { width: 1920, height: 1080 };
    //console.log('URL: ' + window.location.href);
    page.render('image'+testindex+'.png');
    testindex++;
  }
  if (typeof steps[testindex] != "function") {
    //console.log("test complete!");
    phantom.exit(1);
  }
}, 500);

phantom.onError = function(msg, trace) {
	//console.log(JSON.stringify(msg));
    //phantom.exit(1);
};
