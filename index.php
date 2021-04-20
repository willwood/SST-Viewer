<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- SST Chart Viewer - Designed and developed by William Woodgate BSc - willwoodgate.com -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="resources/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="resources/font-awesome-icons/css/all.css">
    <link rel="stylesheet" type="text/css" href="resources/styles.css">
    <title>SST Chart Viewer</title>
  </head>
  <body>
    <?php
      if(empty($_GET['chart-type'])) {
        $chart_type = 'sst'; // Default
      } else {
        $chart_type = $_GET['chart-type'];
      }
      if(empty($_GET['region'])) {
        $chart_region = "wnc"; // Default
      } else {
        $chart_region = $_GET['region'];
      }
      $local_gif_path = "cached-images/$chart_type/$chart_region";
      $oldest_date = date('Y-m-d',strtotime("-32 days"));
      $newest_date = date('Y-m-d',strtotime("-1 days"));
      $start = new DateTime($oldest_date);
      $end = new DateTime($newest_date);
      $interval = new DateInterval("P1D");
      $range = new DatePeriod($start, $interval, $end);
      $slide_number = 1;
      // Generate a multidimensional array to store all the dates in
      $my_array;
      foreach ($range as $date) {
        $year = $date->format("Y");
        $long_date = $date->format("Y-m-d");
        $short_date = $date->format("Ymd");
        $pretty_date = $date->format("D j M Y");
        $my_array[] = array("year"=>$year, "long_date"=>$long_date, "short_date"=>$short_date, "pretty_date"=>$pretty_date);
      }
      // Output each date item and construct the slider
      echo ('<div id="viewer-container"><div id="main-container"><button class="btn btn-dark btn-lg toggle" id="toggle-controls" onclick="toggleControls();"><i class="fas fa-list"></i></button>');
      echo ('<div id="slideshow-container">');
      foreach (array_reverse($my_array) as $date_item) {
        echo ('<div class="mySlides" data-region="'.$chart_region.'" data-long-date="'.$date_item['long_date'].'" data-short-date="'.$date_item['short_date'].'">');
        if (file_exists($local_gif_path."/".$date_item['short_date'].".gif") == TRUE) {
          echo ('<img src="'.$local_gif_path.'/'.$date_item["short_date"].'.gif" alt="'.$date_item["pretty_date"].'" loading="lazy">');
        }
        else {
          if ($chart_region === "global") {
            $remote_image_header = get_headers('https://coralreefwatch.noaa.gov/data/5km/v3.1/image/daily/'.$chart_type.'/gif/'.$date_item["year"].'/coraltemp5km_'.$chart_type.'_'.$date_item["short_date"].'_large.gif');
            $remote_image_url = 'https://coralreefwatch.noaa.gov/data/5km/v3.1/image/daily/'.$chart_type.'/gif/'.$date_item["year"].'/coraltemp5km_'.$chart_type.'_'.$date_item["short_date"].'_large.gif';
          } elseif ($chart_region === "east") {
            $remote_image_header = get_headers('https://coralreefwatch.noaa.gov/data/5km/v3.1/image/daily/'.$chart_type.'/gif/'.$date_item["year"].'/coraltemp5km_'.$chart_type.'_'.$date_item["short_date"].'_east.gif');
            $remote_image_url = 'https://coralreefwatch.noaa.gov/data/5km/v3.1/image/daily/'.$chart_type.'/gif/'.$date_item["year"].'/coraltemp5km_'.$chart_type.'_'.$date_item["short_date"].'_east.gif';
          } elseif ($chart_region === "west") {
            $remote_image_header = get_headers('https://coralreefwatch.noaa.gov/data/5km/v3.1/image/daily/'.$chart_type.'/gif/'.$date_item["year"].'/coraltemp5km_'.$chart_type.'_'.$date_item["short_date"].'_west.gif');
            $remote_image_url = 'https://coralreefwatch.noaa.gov/data/5km/v3.1/image/daily/'.$chart_type.'/gif/'.$date_item["year"].'/coraltemp5km_'.$chart_type.'_'.$date_item["short_date"].'_west.gif';
          } else {
            $remote_image_header = get_headers('https://coralreefwatch.noaa.gov/data/5km/v3.1/image/daily/'.$chart_type.'/gif/'.$date_item["year"].'/coraltemp5km_'.$chart_type.'_'.$date_item["short_date"].'_'.$chart_region.'_2160x1620.gif');
            $remote_image_url = 'https://coralreefwatch.noaa.gov/data/5km/v3.1/image/daily/'.$chart_type.'/gif/'.$date_item["year"].'/coraltemp5km_'.$chart_type.'_'.$date_item["short_date"].'_'.$chart_region.'_2160x1620.gif';
          }
          
          if ($remote_image_header[0] == 'HTTP/1.1 200 OK') {
            if (!file_exists($local_gif_path)) {
              mkdir($local_gif_path, 0755, true);
            }
            $img = $local_gif_path.'/'.$date_item["short_date"].'.gif';
            file_put_contents($img, file_get_contents($remote_image_url));
            echo ('<img src="'.$local_gif_path.'/'.$date_item["short_date"].'.gif" alt="'.$date_item["pretty_date"].'" loading="lazy">');
          } else {
            echo "The file doesn't exist at NOAA and no cached image is available on our server either.";
          }
        }
        echo ('</div>'); 
      }
      echo ('<a class="prev btn btn-dark btn-lg" onclick="plusSlides(-1)" title="Previous"><i class="fas fa-chevron-left"></i></a><a class="next btn btn-dark btn-lg" onclick="plusSlides(1)" title="Next"><i class="fas fa-chevron-right"></i></a></div></div>');
      // Delete older images in the cache
      $fileSystemIterator = new FilesystemIterator($local_gif_path);
      $now = time();
      foreach ($fileSystemIterator as $file) {
        if ($now - $file->getCTime() >= 60 * 60 * 24 * 30) // 30 days 
        unlink($local_gif_path.'/'.$file->getFilename());
      }
      // Generate sidebar list group
      echo ('<div id="controls-container">');
      echo ('<form method="get" action=""><label for="chart-type" class="form-label bolder">Chart Type:</label><select id="charttype" class="form-select form-select-md mb-3" name="chart-type" title="Use this select menu to change the chart type displayed." aria-label="SST (default)"><option value="ssta">SST Anomalies</option><option value="sst" selected>SST</option></select><label for="region" class="form-label bolder">Region:</label><p><input type="radio" class="standalone-radio-selection" name="region" value="global" title="Global" id="global" /> <label for="global">Global</label></p><p><input type="radio" class="standalone-radio-selection" name="region" value="east" title="Eastern Hemisphere" id="east" /> <label for="east">Eastern Hemisphere</label></p><p><input type="radio" class="standalone-radio-selection" name="region" value="west" title="Western Hemisphere" id="west" /> <label for="west">Western Hemisphere</label></p><p>Or select a tropical ocean region below...</p><div id="map-box"><div id="map-squares"> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="enw" title="Eastern Hemisphere, West" id="enw" /> <label for="enw"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="enc" title="Eastern Hemisphere, Center" id="enc" /> <label for="enc"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="ene" title="Eastern Hemisphere, East" id="ene" /> <label for="ene"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="wnw" title="Western Hemisphere, West" id="wnw" /> <label for="wnw"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="wnc" checked title="Western Hemisphere, Center" id="wnc" /> <label for="wnc"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="wne" title="Western Hemisphere, East" id="wne" /> <label for="wne"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="esw" title="Eastern Hemisphere, West" id="esw" /> <label for="esw"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="esc" title="Eastern Hemisphere, Center" id="esc" /> <label for="esc"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="ese" title="Eastern Hemisphere, East" id="check09" /> <label for="check09"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="wsw" title="Western Hemisphere, West" id="wsw" /> <label for="wsw"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="wsc" title="Western Hemisphere, Center" id="wsc" /> <label for="wsc"></label> </span> <span class="map-radio-item"> <input type="radio" class="region-radio-selection" name="region" value="wse" title="Western Hemisphere, East" id="wse" /> <label for="wse"></label> </span></div></div><div class="d-grid gap-2"><button type="submit" class="btn btn-primary">Load Images</button></div><hr></form>');
      echo ('<ul class="list-group">');
      foreach (array_reverse($my_array) as $date_item) {
        echo ('<li class="list-group-item date-item"><span onclick="currentSlide('.$slide_number.')">'.$date_item["pretty_date"].'</span><div class="btn-group" role="group"><a href="'.$local_gif_path.'/'.$date_item["short_date"].'.gif" class="btn btn-secondary" id="new-tab-btn" target="_blank" title="Open in new tab"><i class="fas fa-external-link-alt"></i></a><a href="'.$local_gif_path.'/'.$date_item["short_date"].'.gif" class="btn btn-secondary" id="download-btn" download="'.$chart_type.'-'.$chart_region.'-'.$short_date.'" title="Download"><i class="fas fa-download"></i></a></div></li>');
        $slide_number += 1;
      }
      echo ('</ul><p><br>Images are provided in the public domain by <a href="https://coralreefwatch.noaa.gov/product/5km/">NOAA&nbsp;Coral&nbsp;Reef&nbsp;Watch</a> and are updated daily.<br><br>You can navigate the chart viewer using the next/previous buttons, by clicking the dates above in the list or using the left/right keys on your keyboard.<br><br><strong>Note:</strong> Some chart types and regions can take several minutes to load and can be up to 30&nbsp;MB in size. Once cached, they\'ll load quicker.</p></div></div>');
    ?>
    <script>
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('chart-type')) {
        const chartTypeParam = urlParams.get('chart-type');
        document.querySelector('#charttype').value = chartTypeParam;
      }
      if (urlParams.has('region')) {
        const regionParam = urlParams.get('region');
        document.getElementById(regionParam).checked = true;
      }

      function toggleControls() {
        document.querySelector('body').classList.toggle('hide-controls');
      }

      var slideIndex = 1;
      showSlides(slideIndex);

      // Next/previous controls
      function plusSlides(n) {
        showSlides(slideIndex += n);
      }

      // Thumbnail image controls
      function currentSlide(n) {
        showSlides(slideIndex = n);
      }

      // Keyboard navigation
      document.onkeydown = function(event) {
        switch (event.keyCode) {
          case 37:
            plusSlides(-1)
            break;
          case 39:
            plusSlides(1)
            break;
        }
      };

      function showSlides(n) {
        var i;
        var slides = document.getElementsByClassName("mySlides");
        var dots = document.getElementsByClassName("date-item");
        if (n > slides.length) {slideIndex = 1}
        if (n < 1) {slideIndex = slides.length}
        for (i = 0; i < slides.length; i++) {
          slides[i].style.display = "none";
        }
        for (i = 0; i < dots.length; i++) {
          dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex-1].style.display = "block";
        dots[slideIndex-1].className += " active";
      } 
    </script>
  </body>
</html>