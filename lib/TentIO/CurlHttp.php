<?php
/**
 * @license MIT
 */
class TentIO_CurlHttp extends TentIO_AbstractHttp {

  public static $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'php-tent-client-0.1',
  );

  function __construct() {
    if (!function_exists('curl_init')) {
      throw new Exception('CurlTentHttp requires the CURL PHP extension.');
    }
  }

  function request($url, $options = array()) {
    $ch = curl_init();

    $curl_opts = self::$CURL_OPTS;

    if (!isset($options['method'])) {
      $options['method'] = 'GET';
    }

    if ($options['method'] === 'HEAD') {
      $curl_opts[CURLOPT_NOBODY] = true;
    } else {
      $curl_opts[CURLOPT_CUSTOMREQUEST] = strtoupper($options['method']);
    }
    
    if (isset($options['body'])) {
      if ($curl_opts[CURLOPT_CUSTOMREQUEST] === 'GET') {
        $curl_opts[CURLOPT_URL] = $url . '?' . http_build_query($options['body'], null, '&');
      } else {
        $curl_opts[CURLOPT_URL] = $url;
        $curl_opts[CURLOPT_POSTFIELDS] = $data = json_encode($options['body']);
      }
    } else {
      $curl_opts[CURLOPT_URL] = $url;
    }

    if (isset($options['file'])) {

    }

    if (isset($options['headers'])) {
      $curl_opts[CURLOPT_HTTPHEADER] = $options['headers'];
    }
    
    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    if (isset($curl_opts[CURLOPT_HTTPHEADER])) {
      $existing_headers = $curl_opts[CURLOPT_HTTPHEADER];
      $existing_headers[] = 'Expect:';
      $curl_opts[CURLOPT_HTTPHEADER] = $existing_headers;
    } else {
      $curl_opts[CURLOPT_HTTPHEADER] = array('Expect:');
    }

    $curl_opts[CURLOPT_HEADER] = true;

    curl_setopt_array($ch, $curl_opts);

    // print_r(implode("\n", $curl_opts[CURLOPT_HTTPHEADER]));
    // echo "\n";
    // echo $curl_opts[CURLOPT_POSTFIELDS];
    // echo "\n\n";

    $result = curl_exec($ch);

    // if (curl_errno($ch) == 60) { // CURLE_SSL_CACERT
    //   TentLog::error('Invalid or no certificate authority found, using bundled information');
    //   curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/fb_ca_chain_bundle.crt');
    //   $result = curl_exec($ch);
    // }

    // With dual stacked DNS responses, it's possible for a server to
    // have IPv6 enabled but not have IPv6 connectivity.  If this is
    // the case, curl will try IPv4 first and if that fails, then it will
    // fall back to IPv6 and the error EHOSTUNREACH is returned by the
    // operating system.
    if ($result === false && empty($curl_opts[CURLOPT_IPRESOLVE])) {
      $matches = array();
      $regex = '/Failed to connect to ([^:].*): Network is unreachable/';
      if (preg_match($regex, curl_error($ch), $matches)) {
        if (strlen(@inet_pton($matches[1])) === 16) {
          TentIO_Log::error('Invalid IPv6 configuration on server, Please disable or get native IPv6 on your server.');
          self::$CURL_OPTS[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
          curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
          $result = curl_exec($ch);
        }
      }
    }

    // echo $result;

    $headers = false;
    $body = false;
    if ($result) {
      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $raw = explode("\n", substr($result, 0, $header_size));
      foreach($raw as $header) {
        if ($name = substr($header, 0, strpos($header, ':'))) {
          $value = substr($header, strpos($header, ':')+1);
          $headers[$name] = $value;
        }
      }
      $body = substr($result, $header_size);
    }

    return TentIO_Response::create($body, curl_getinfo($ch, CURLINFO_HTTP_CODE), $headers, 'remote');
  }
}


