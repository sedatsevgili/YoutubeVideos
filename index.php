<?php 
// Call set_include_path() as needed to point to your client library. 
require_once 'google-api-php-client/src/Google_Client.php'; 
require_once 'google-api-php-client/src/contrib/Google_YouTubeService.php'; 
require_once 'config.php';
$CLIENT_ID = '';
$CLIENT_SECRET = '';
session_start(); 
/* You can acquire an OAuth 2 ID/secret pair from the API Access tab on the Google APIs Console
  <http://code.google.com/apis/console#access> For more information about using OAuth2 to access Google APIs, please visit:
  <https://developers.google.com/accounts/docs/OAuth2> Please ensure that you have enabled the YouTube Data API for your project. */
$client = new Google_Client(); 
$client->setClientId($CLIENT_ID);
$client->setClientSecret($CLIENT_SECRET); 
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],  FILTER_SANITIZE_URL); 
$client->setRedirectUri($redirect); 
$youtube = new Google_YoutubeService($client); 
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }
  $client->authenticate();
  $_SESSION['token'] = $client->getAccessToken();
  header('Location: ' . $redirect);
}
if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}
if ($client->getAccessToken()) {
  try {
      $maxResults = intval($_GET['maxResults']);
      if($maxResults > 50) {
          $maxResults = 50;
      } else if ($maxResults <= 0) {
          $maxResults = 10;
      }
      $keyword = htmlspecialchars($_GET['q']);
      $searchResponse = array();
      if(!empty($keyword)) {
          $searchResponse = $youtube->search->listSearch('id,snippet', array(
              'q' => $keyword,
              'maxResults' => $maxResults,
            ));
      }
    
    
    $videos = '';
    $channels = '';
    $playlists = '';
    $htmlBody = '';
    
    foreach ($searchResponse['items'] as $searchResult) {
        switch ($searchResult['id']['kind']) {
        case 'youtube#video':
          $videos .= sprintf('<li>%s (%s)</li>',
              $searchResult['snippet']['title'], $searchResult['id']['videoId']);
          break;
        case 'youtube#channel':
          $channels .= sprintf('<li>%s (%s)</li>',
              $searchResult['snippet']['title'], $searchResult['id']['channelId']);
          break;
        case 'youtube#playlist':
          $playlists .= sprintf('<li>%s (%s)</li>',
              $searchResult['snippet']['title'], $searchResult['id']['playlistId']);
          break;
      }   
    }
    
    $htmlBody .= <<<END
    <h3>Videos</h3>
    <ul>$videos</ul>
    <h3>Channels</h3>
    <ul>$channels</ul>
    <h3>Playlists</h3>
    <ul>$playlists</ul>
END;

  } catch (Google_ServiceException $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  }
  $_SESSION['token'] = $client->getAccessToken();
} else {
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;
  $authUrl = $client->createAuthUrl();
  $htmlBody = '
  <h3>Authorization Required</h3>
  <p>You need to <a href="' . $authUrl . '">authorize access</a> before proceeding.<p>';
}
?> 
<!doctype html> <html>
  <head>
    <title>My Uploads</title>
  </head>
  <body>
    Search on youtube
    <form method="GET">
        Keyword: <input type="text" name="q" value="<?php echo $keyword; ?>" /><br>
        Max Results: <input type="text" name="maxResults" value = "<?php echo $maxResults; ?>"/><br>
        <input type="submit" value="Search" />
    </form>
    <?php echo $htmlBody; ?>
</body> </html>