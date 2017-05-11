<?php

/**
 * Pomocná třída pro získání OAuth2 credentials
 *
 * @author František Kaša <frantisekkasa@gmail.com>
 * @date 5.3.2017
 * 
 * @project xreporty_1.2
 * @name OAuth2Authentication
 */

namespace App\AdsMyExtension;

use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\ReportSettingsBuilder;

class OAuth2Authentication extends \Nette\Object
{

    /** @var string Soubor s daty pro přihlášení k OAuth2 */
    protected $adsapi_file_path = 'adsapi_php.ini';
    /** @var string OAuth2ClientId */
    protected $clientId;
    /** @var string OAuth2ClientSecret */
    protected $clientSecret;
    /** @var string OAuth2RefreshToken */
    protected $refreshToken;
    /** @var string OAuth2ClientCustomerId */
    protected $clientCustomerId;
    /** @var string OAuth2DeveloperToken */
    protected $developerToken;
    /** @var  bool */
    protected $reportHeader = true;
    /** @var  bool */
    protected $columnHeader = true;
    /** @var  bool */
    protected $reportSummary = true;
    /** @var  bool */
    protected $includeImpressions = true;

    public function __construct($adsapiFile = NULL, $clientId = NULL, $clientSecret = NULL, $refreshToken = NULL, $clientCustomerId = NULL, $developerToken = NULL)
    {
        $this->adsapi_file_path = $adsapiFile;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
        $this->clientCustomerId = $clientCustomerId;
        $this->developerToken = $developerToken;
    }

    public function setClientCustomerId($clientCustomerId)
    {
    	$this->clientCustomerId = $clientCustomerId;
    }

    public function getClientCustomerId()
    {
    	return $this->clientCustomerId;
    }

    public function setClientId($clientId)
    {
    	$this->clientId = $clientId;
    }

    public function setClientSecret($clientSecret)
    {
    	$this->clientSecret = $clientSecret;
    }

    public function setRefreshToken($refreshToken)
    {
    	$this->refreshToken = $refreshToken;
    }


    public function setDeveloperToken($developerToken)
    {
    	$this->developerToken = $developerToken;
    }

    private function getOAuthCredential()
    {
	    // Generate a refreshable OAuth2 credential for authentication.
	    $oAuth2Credential = (new OAuth2TokenBuilder())
		    ->fromFile($this->adsapi_file_path)
		    ->withClientId($this->clientId)
		    ->withClientSecret($this->clientSecret)
		    ->withRefreshToken($this->refreshToken)
		    ->build();

	    return $oAuth2Credential;
    }

    public function getReportSettings()
    {
	    // See: ReportSettingsBuilder for more options (e.g., suppress headers)
	    // or set them in your adsapi_php.ini file.
	    $reportSettings = (new ReportSettingsBuilder())
		    ->fromFile($this->adsapi_file_path)
		    ->includeZeroImpressions($this->includeImpressions)
		    ->skipReportHeader($this->reportHeader)
		    ->skipColumnHeader($this->columnHeader)
		    ->skipReportSummary($this->reportSummary)
		    ->build();

	    return $reportSettings;
    }

    public function setReportSettings($includeImpressions = true, $reportHeader = true, $columnHeader = true, $reportSummary = true)
    {
    	$this->includeImpressions   = $includeImpressions;
    	$this->reportHeader         = $reportHeader;
    	$this->columnHeader         = $columnHeader;
    	$this->reportSummary        = $reportSummary;
    }

    public function getConstructApiSession()
    {
        // Construct an API session configured from a properties file and the OAuth2
        // credentials above.
        $session = (new AdWordsSessionBuilder())
            ->fromFile($this->adsapi_file_path)
            ->withOAuth2Credential(self::getOAuthCredential())
            ->withClientCustomerId($this->clientCustomerId)
            ->withDeveloperToken($this->developerToken)
            ->withReportSettings(self::getReportSettings())
            ->build();

        return $session;
    }

}
