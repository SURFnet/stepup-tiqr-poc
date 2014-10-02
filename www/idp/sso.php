<?php

include('../../config.php');
include('../saml.php');

# sample user store - in reality you would connect to something like LDAP
$directory = array(
	'john' => array(
		'uid'	=> 'john',
		'sn'	=> 'Doe',
		'givenName'	=> 'John',
		'mail'	=> 'john.doe@example.org',
	),
);

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost:8080';

// SP data
#$acs = "$proto://$host/acs.php";
#$acs = $config['sp']['http://localhost:8080/sp/metadata.php']['acs'];
$audience = "http:/gw.com/";	# TODO

// local IDP
$issuer = htmlspecialchars('http://idp.org/');
$keyfile = 'key.pem';
$certfile = 'cert.pem';

$samlrequest = $_GET['SAMLRequest'];
$relaystate = array_key_exists('RelayState', $_GET) ? $_GET['RelayState'] : NULL;
#$sigalg = $_GET['SigAlg'];
#$signature = $_GET['Signature'];

$request = gzinflate(base64_decode($samlrequest));

$xml = str_replace ("\r", "", $request);
$dom = new DOMDocument();
$dom->loadXML($xml);

if ($dom->getElementsByTagName('AuthnRequest')->length === 0) {
  throw new Exception('Unknown request on saml20 endpoint!');
}

$requestor = $dom->getElementsByTagName('Issuer')->item(0)->textContent;
$authnrequest = $dom->getElementsByTagName('AuthnRequest')->item(0);
$sprequestid = $authnrequest->getAttribute('ID');
$acs = $authnrequest->getAttribute('AssertionConsumerServiceURL');
error_log('ACS: '.$acs);
$destination = htmlspecialchars($acs);

$attrnameformat = NULL;


# assume solicited responses
$inResponseTo = htmlspecialchars($sprequestid);

$id = "_";
for ($i = 0; $i < 42; $i++ ) $id .= dechex( rand(0,15) );

$assertionid = '';
for ($i = 0; $i < 42; $i++ ) $assertionid .= dechex( rand(0,15) );

$issueinstant = gmdate("Y-m-d\TH:i:s\Z", time() );
$notonorafter = gmdate("Y-m-d\TH:i:s\Z", time() + 60 * 5);
$notbefore = gmdate("Y-m-d\TH:i:s\Z", time() - 30);

$subject = htmlspecialchars('john');

### authentication statement

# let's pretend we actually authenticated 'joe' using his password
$authnStatement = <<<XML
  <saml:AuthnStatement AuthnInstant="$issueinstant" SessionIndex="1"
  >
   <saml:AuthnContext>
    <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
   </saml:AuthnContext>
  </saml:AuthnStatement>
XML;

### attribute statement

# We're ignoring multivalued attributes here...
$attributes = "";
foreach( $directory['john'] as $name => $value) {
	$attributes .= <<<ATTR
   <saml:Attribute Name="$name">
    <saml:AttributeValue>$value</saml:AttributeValue>
   </saml:Attribute>
ATTR;
}

$attributeStatement = <<<AS
  <saml:AttributeStatement>
$attributes
  </saml:AttributeStatement>
AS;

### response

$xml = <<<XML
<samlp:Response
	xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
	xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
	ID="$id"
	InResponseTo="$inResponseTo"
	Version="2.0"
	IssueInstant="$issueinstant"
	Destination="$destination">
 <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">$issuer</saml:Issuer>
 <samlp:Status xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol">
  <samlp:StatusCode xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
 </samlp:Status>
 <saml:Assertion Version="2.0" ID="$assertionid" IssueInstant="$issueinstant">
  <saml:Issuer>$issuer</saml:Issuer>
   <saml:Subject>
    <saml:NameID Format='urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'>$subject</saml:NameID>
    <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
     <saml:SubjectConfirmationData NotOnOrAfter="$notonorafter" InResponseTo="$inResponseTo" Recipient="$destination" /></saml:SubjectConfirmation>
   </saml:Subject>
   <saml:Conditions NotBefore="$notbefore" NotOnOrAfter="$notonorafter">
    <saml:AudienceRestriction>
     <saml:Audience>$audience</saml:Audience>
    </saml:AudienceRestriction>
   </saml:Conditions>
$authnStatement
$attributeStatement
 </saml:Assertion>
</samlp:Response>
XML;

$dom = new DOMDocument();
$dom->preserveWhiteSpace = FALSE;
$dom->loadXML($xml);
$dom->formatOutput = TRUE;

$response = $dom;
// sign the assertion
// do not add certificate
$response = utils_xml_sign($response, $keyfile, $certfile);

$params = array();
$params['SAMLResponse']	= base64_encode($response->saveXML());
if ($relaystate !== NULL) {
  $params['RelayState'] = $relaystate;
}

?>
<form
	method="post"
	action="<?php echo htmlspecialchars($destination)?>"
>
<?php foreach ($params as $key => $value) { ?>
  <input
	type="hidden"
	name="<?php echo htmlspecialchars($key)?>"
	value="<?php echo htmlspecialchars($value)?>"
  />
<?php } ?>
    <input type="text" name="username" value="john" />
    <input type="password" name="password" value="secret" autofocus="autofocus" />
<input type="submit" value="Login" />
</form>
