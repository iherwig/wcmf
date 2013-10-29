<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>wCMF - Installation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="../app/public/vendor/twitter-bootstrap/css/bootstrap.css" rel="stylesheet">
  <link href="../app/public/vendor/twitter-bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

  <style>
    section {
      margin-top: 20px;
    }
  </style>
  <script>
    function runScript(script, btn) {
      var xhr = new XMLHttpRequest();
      var oldBtnText = btn.innerHTML;
      btn.innerHTML = oldBtnText+" ...";
      document.getElementById("result").innerHTML =
              '<p><div class="progress progress-info progress-striped active">'+
              '<div class="bar" style="width:100%;"></div></p>';
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
          var resultText = '';
          if (xhr.status === 200) {
            resultText += '<p><pre>'+xhr.responseText+'</pre></p>';
          }
          else {
            resultText += '<p><div class="alert alert-error">'+xhr.responseText+'</div></p>';
          }
          btn.innerHTML = oldBtnText;
          document.getElementById("result").innerHTML = resultText;
        }
      }
      xhr.open("GET", script, true);
      xhr.send();
    }
</script>
</head>
<body>
  <div class="container">
    <div class="row">
      <div class="span9">
        <section id="what-next">
          <div class="page-header">
            <h1>wCMF Installation</h1>
          </div>
          <p class="lead">You can either update the database scheme or load initial data</p>
          <div class="btn-toolbar">
            <a class="btn btn-large btn-primary" href="#" onclick="runScript('../wcmf/tools/database/dbupdate.php', this);">Update database</a>
            <a class="btn btn-large" href="#" style="margin-left: 5px;" onclick="runScript('../wcmf/tools/database/install.php', this);">Initialize database</a>
          </div>
        </section>
        <section id="result">
        </section>
      </div>
    </div>
  </div>
</body>
</html>
