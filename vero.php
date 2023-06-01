
<title>Vero Checker</title>
<link rel="shortcut icon" type="image/x-icon" href="favicon.png">

<style>
/* Style untuk table */
body {
  font-family: Arial, sans-serif;
  background-color: #f2f2f2; /* background abu smooth */
}

.container {
  width: 70%;
  margin: 0 auto;
  background-color: #fff;
  padding: 20px;
  box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.3);
}

table {
  border-collapse: collapse;
  width: 100%;
  margin-bottom: 20px;
}

th, td {
  text-align: left;
  padding: 8px;
  border: 1px solid #ddd;
  font-family: Arial, sans-serif;
}

th {
  background-color: #28a745;
  color: #fff;
}

/* Style untuk teks merah */
.merah {
  color: #dc3545;
  font-weight: bold;
}

.merah-bg {
  background-color: #ffe6e6;
}

form {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 10px;
}

textarea {
  width: 100%;
  height: 200px;
  padding: 8px;
  border: 2px solid #bbb;
  border-radius: 4px;
  resize: vertical;

}
textarea:focus {
  outline: none;
  scrollbar-width: thin;


}
textarea::-webkit-scrollbar {
  width: 8px;
  cursor: pointer;
}

textarea::-webkit-scrollbar-thumb {
  background-color: #ddd;
  border-radius: 10px;
  cursor: pointer;
}

textarea::-webkit-scrollbar-thumb:hover {
  background-color: #ccc;
  cursor: pointer;
}

textarea::-webkit-scrollbar-track {
  background-color: #f2f2f2;
  border-radius: 10px;
  cursor: pointer;
}


button[type="submit"] {
  background-color: #4CAF50;
  color: white;
  padding: 10px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 10px; /* tambahkan jarak 10px dari atas */
}

button[type="submit"]:hover {
  background-color: #3e8e41;
}

button[type="button"] {
  background-color: #f44336;
  color: white;
  padding: 10px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 10px;
  margin-right: 10px;
}

button[type="button"]:hover {
  background-color: #d32f2f;
}
.input-group-text {
  background-color: #fff;
  border: none;
}

#asin-input {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  resize: none;
}


#submit-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
#row-count {
  margin-right: 0.5rem;
}

#warning-msg {
  margin-top: 0.5rem;
  color: #dc3545;
}

#asin-input:invalid {
  border-color: #dc3545;
}
.input-container {
  position: relative;
}

#counter {
  position: absolute;
  bottom: 0;
  right: 0;
  font-size: 12px;
  color: #999;
  margin-bottom: 5px;
  margin-right: 15px;
}

#error-msg {
  color: red;
  margin-top: 10px;
  font-size: 13px;
}

.border-danger {
  border: 1px solid red;
}

img[alt="www.000webhost.com"]{display:none;}
</style>

<?php
if(!empty($_SERVER['HTTP_USER_AGENT'])) { 
    $userAgents = array("Google", "Slurp", "MSNBot", "ia_archiver", "Yandex", "Rambler"); 
    if(preg_match('/' . implode('|', $userAgents) . '/i', $_SERVER['HTTP_USER_AGENT'])) { 
        header("HTTP/1.0 404 Not Found"); 
        exit; 
    } 
} 
ini_set('upload_max_filesize', '0');
ini_set('post_max_size', '0');
ini_set('max_execution_time', '0');
@error_reporting(0);

function grab_multi($urls) {
    $mh = curl_multi_init();
    $ch = [];

    // Create cURL handles for each URL
    foreach ($urls as $i => $url) {
        $ch[$i] = curl_init();
        curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch[$i], CURLOPT_URL, $url);
        curl_setopt($ch[$i], CURLOPT_COOKIE, 'session=eyJ1c2VyX2lkIjoieWViYWw5QG1haWxuZXNpYS5jb20ifQ.ZHcKcw.Q0fzJG7NtuR9AxDFDCAiLk3uapQ');
        curl_multi_add_handle($mh, $ch[$i]);
    }

    $running = null;
    do {
        // Execute the cURL requests
        curl_multi_exec($mh, $running);

        // Update output each time a response is received
        while ($info = curl_multi_info_read($mh)) {
            if ($info['msg'] == CURLMSG_DONE) {
                $handle = $info['handle'];
                $i = array_search($handle, $ch);
                $responses[$i] = curl_multi_getcontent($handle);
                curl_multi_remove_handle($mh, $handle);
                curl_close($handle);
                ob_flush();
                flush();
            }
        }
    } while ($running > 0);

    curl_multi_close($mh);

    return $responses;
}

$show_form = true;

if(isset($_POST['asins'])){
  // get ASINs from textarea and split by newline
  $asins = explode("\n", htmlspecialchars($_POST['asins']));
  $asins = array_filter($asins, function($value) {
    return trim($value) !== '';
  });

  echo "<div class='container'><table>";
  echo "<tr><th>ASIN</th><th>Vero BRAND</th><th>Vero ASIN</th><th>Blacklist Warning</th><th>Keterangan</th></tr>";

  
  // Create an array of URLs for each ASIN
  $urls = array_map(function ($asin) {
      return "https://yaballe.com/source/amazon.com/" . trim($asin) . "?is_condition_new=true";
  }, $asins);

  // Send requests and get responses
  $responses = grab_multi($urls);

  // Loop through each ASIN and print the response data
for($i = 0; $i < count($asins); $i++) {
    // decode JSON response
    $data = json_decode($responses[$i], true);

    // check for error response
    if(isset($data['error'])){
        // print error message for current ASIN in a row with all columns in red
        echo "<tr>";
        echo "<td class='merah merah-bg'><b>" . $asins[$i] . "</b></td>";
        echo "<td class='merah merah-bg'><b></b></td>";
        echo "<td class='merah merah-bg'><b></b></td>";
        echo "<td class='merah merah-bg'><b></b></td>";
        echo "<td class='merah merah-bg'><b>" . $data['error'] . "</b></td>";
        echo "</tr>";
    } else {
        // access vero_warnings data
        $vero_warnings = $data['data']['vero_warnings']['brand'];
        // access protected_reason data
        $protected_reason = $data['data']['protected_reason'];
        $vero_asin = $data['data']['vero_warnings']['ASIN'];
        $blacklist = $data['data']['blacklist'];
        $blacklist = implode(", ", $blacklist);


        // print vero_warnings and protected_reason data for current ASIN in a row with all columns in red if either of them is not empty
        echo "<tr" . (($protected_reason != null || !empty($vero_warnings) || !empty($vero_asin) || !empty($blacklist)) ? " class='merah merah-bg'" : "") . ">";
        echo "<td" . (($protected_reason != null || !empty($vero_warnings) || !empty($vero_asin) || !empty($blacklist)) ? " class='merah merah-bg'" : "") . ">" . $asins[$i] . "</td>";
        echo "<td" . (($protected_reason != null || !empty($vero_warnings) || !empty($vero_asin) || !empty($blacklist)) ? " class='merah merah-bg'" : "") . ">" . (!empty($vero_warnings) ? $vero_warnings[0] : "") . "</td>";
        echo "<td" . (($protected_reason != null || !empty($vero_warnings) || !empty($vero_asin) || !empty($blacklist)) ? " class='merah merah-bg'" : "") . ">" . (!empty($vero_asin) ? $vero_asin[0] : "") . "</td>";
        echo "<td" . (($protected_reason != null || !empty($vero_warnings) || !empty($vero_asin) || !empty($blacklist)) ? " class='merah merah-bg'" : "") . ">" . (!empty($blacklist) ? $blacklist : "") . "</td>";
echo "<td" . (($protected_reason != null || !empty($vero_warnings) || !empty($vero_asin) || !empty($blacklist)) ? " class='merah merah-bg'" : "") . ">" . ($protected_reason != null ? $protected_reason : (!empty($vero_warnings) || !empty($vero_asin) || !empty($blacklist) ? "VERO Jangan dilisting" : "")) . "</td>";


    }
  }
  echo "</table>";
  // add download CSV button
  echo '<div><button type="button" onclick="location.href=\''. $_SERVER['PHP_SELF'] .'\'">Back to form</button><button onclick="downloadTable()" type="submit">Download CSV</button></div>';

  // add JavaScript function to download table data as CSV
  echo '<script>
          function downloadTable() {
            let table = document.getElementsByTagName("table")[0];
            let rows = table.rows;
            let csv = "";
            for (let i = 0; i < rows.length; i++) {
              let cells = rows[i].cells;
              for (let j = 0; j < cells.length; j++) {
                csv += cells[j].innerText;
                if (j < cells.length - 1) {
                  csv += ",";
                }
              }
              csv += "\\n";
            }
            let link = document.createElement("a");
            link.setAttribute("href", "data:text/csv;charset=utf-8," + encodeURI(csv));
            link.setAttribute("download", "Cek-VERO.csv");
            link.click();
          }
        </script>';


  // set show_form to false to hide the form
  $show_form = false;
}
?>
<html>

<!-- HTML input form -->
<?php if($show_form): ?>
<div class="container">
  <form method="post">
    <label for="asin-input">ASINs:</label>
    <div class="input-container">
      <textarea id="asin-input" name="asins" placeholder="B09NZJYDVW
B08VDQQY37
B092HVDCW7"></textarea>
      <span id="counter">0/300</span>
    </div>
    <div id="error-msg"></div>
    <button type="submit" id="submit-btn" disabled>Cek VERO</button>
    
  </form>
</div>

<style>
  .border-danger {
    border: 1px solid red;
  }
</style>

<script>
const textarea = document.querySelector("#asin-input");
const submitBtn = document.querySelector("button[type='submit']");
const counter = document.querySelector("#counter");
const error = document.querySelector("#error-msg");

const MAX_LINES = 500;

function countLines() {
  const value = textarea.value;
  const lines = value.split(/\r|\r\n|\n/).filter((line) => line.trim() !== "");
  return lines.length;
}

function checkValidity() {
  const numLines = countLines();
  if (numLines === 0) {
    submitBtn.disabled = true;
    counter.textContent = `${numLines}/${MAX_LINES}`;
    error.textContent = "";
  } else if (numLines > MAX_LINES) {
    textarea.classList.add("border-danger");
    submitBtn.disabled = true;
    counter.textContent = `${numLines}/${MAX_LINES}`;
    error.textContent = "Maksimal 500 ASIN";
  } else {
    textarea.classList.remove("border-danger");
    submitBtn.disabled = false;
    counter.textContent = `${numLines}/${MAX_LINES}`;
    error.textContent = "";
  }
}


textarea.addEventListener("input", checkValidity);

checkValidity();

</script>

<?php endif; ?>
</html>
