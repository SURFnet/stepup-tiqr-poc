<?php
require_once __DIR__.'/../../vendor/autoload.php';

include('Tiqr/Service.php');
include('../../options.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application(); 
$app['debug'] = true;

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->before(function ($request) {
    $request->getSession()->start();
});

$tiqr = new Tiqr_Service($options);

$app->get('/', function (Request $request) use ($app, $tiqr) {
    $base = $request->getUriForPath('/');
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect($base.'login');
    }

    // starting a new enrollment session
    $sid = $app['session']->getId();
    $uid = 'john';
    $displayName = "John";	# TODO
    error_log("[$sid] uid is $uid and displayName is $displayName");
    $key = $tiqr->startEnrollmentSession($uid, $displayName, $sid);
    error_log("[$sid] started enrollment session key $key");
    $metadataURL = base() . "/tiqr.php?key=$key";	# TODO
    error_log("[$sid] generating QR code for metadata URL $metadataURL");
    $url = $tiqr->generateEnrollString($metadataURL);
    $qr = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $url;

    $loader = new Twig_Loader_Filesystem('views');
    $twig = new Twig_Environment($loader, array(
    	'debug' => true,
    ));
    $enrol = $twig->render('enrol.html', array(
        'self' => $base,
        'qr' => $qr,
    ));
    $response = new Response($enrol);
    return $response;

});

### status

$app->get('/status', function (Request $request) use ($app, $tiqr) {
    $sid = $app['session']->getId();
    error_log("[$sid]");
    $status = $tiqr->getEnrollmentStatus($sid);
    error_log("[$sid] status is $status");
    return $status;
});

$app->get('/done', function (Request $request) use ($app, $tiqr) {
    $sid = $app['session']->getId();
    $tiqr->resetEnrollmentSession($sid);
    error_log("[$sid] reset enrollment");
    return "done";
});

##########

$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
    #$app->abort(404, "not implemented.");
});

$app->get('/session', function () use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }
    $nameid = $user['username'];
    return 'NameID: '.$app->escape($nameid);
});

########## SAML ##########

# SAML 2.0 Metadata

$app->get('/metadata', function (Request $request) use ($app) {
    $loader = new Twig_Loader_Filesystem('views');
    $twig = new Twig_Environment($loader, array(
    	'debug' => true,
    ));
    $base = $request->getUriForPath('/');
    $metadata = $twig->render('metadata.xml', array(
    	'entityID' => $base . "metadata",	// convention: use metadata URL as entity ID
    	'Location' => $base . "acs",
    ));
    $response = new Response($metadata);
    $response->headers->set('Content-Type', 'text/xml');
    return $response;
});

# send SAML request (SP initiated SAML Web SSO)

$app->get('/login', function (Request $request) use ($app) {
    $relaystate = $request->get('RelayState') or "/";
    $base = $request->getUriForPath('/');
    $issuer = $base . 'metadata';	// convention
    $acs_url = $base . 'acs';
    # remote IDP
    $sso_url = "$base/idp/sso.php";	# TODO config
    $now = gmdate("Y-m-d\TH:i:s\Z", time());
    $id = "_"; for ($i = 0; $i < 42; $i++ ) $id .= dechex( rand(0,15) );

    $loader = new Twig_Loader_Filesystem('views');
    $twig = new Twig_Environment($loader, array(
    	'debug' => true,
    ));
    $request = $twig->render('AuthnRequest.xml', array(
    	'ID' => $id,
    	'Issuer' => $issuer,
    	'IssueInstant' => $now,
    	'Destination' => $sso_url,
    	'AssertionConsumerServiceURL' => $acs_url,
    ));
    # use HTTP-Redirect binding
    $query  = 'SAMLRequest=' . urlencode(base64_encode(gzdeflate($request)));
    $query .= '&RelayState=' . $base;	# TODO: param return
    $location = "$sso_url?$query";
    return $app->redirect($location);
});

# receive SAML response

$app->post('/acs', function (Request $request) use ($app) {
    # TODO: check signature, response, etc
    $response = $request->get('SAMLResponse');
    $relaystate = $request->get('RelayState') or "/";
    $response = base64_decode($response);
    $dom = new DOMDocument();
    $dom->loadXML($response);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('saml', "urn:oasis:names:tc:SAML:2.0:assertion" );
    $query = "string(//saml:Assertion[1]/saml:Subject/saml:NameID)";
    $nameid = $xpath->evaluate($query, $dom);
    if (!$nameid) {
      throw new Exception('Could not locate nameid element.');
    }
    $app['session']->set('user', array('username' => $nameid));
    return $app->redirect($relaystate);
});

$app->run(); 
