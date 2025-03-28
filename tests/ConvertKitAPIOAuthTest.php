<?php

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Dotenv\Dotenv;
use ConvertKit_API\ConvertKit_API;

/**
 * ConvertKit API OAuth Key class tests.
 */
class ConvertKitAPIOAuthTest extends ConvertKitAPITest
{
    /**
     * Load .env configuration into $_ENV superglobal, and initialize the API
     * class before each test.
     *
     * @since   2.2.0
     *
     * @return  void
     */
    protected function setUp(): void
    {
        // Load environment credentials from root folder.
        $dotenv = Dotenv::createImmutable(dirname(dirname(__FILE__)));
        $dotenv->load();

        // Set location where API class will create/write the log file.
        $this->logFile = dirname(dirname(__FILE__)) . '/src/logs/debug.log';

        // Delete any existing debug log file.
        $this->deleteLogFile();

        // Setup API.
        $this->api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET'],
            accessToken: $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN']
        );

        // Wait a second to avoid hitting a 429 rate limit.
        sleep(1);
    }

    /**
     * Test that debug logging works when enabled and an API call is made.
     *
     * @since   2.2.0
     *
     * @return  void
     */
    public function testDebugEnabled()
    {
        // Setup API with debugging enabled.
        $api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET'],
            accessToken: $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
            debug: true
        );
        $result = $api->get_account();

        // Confirm that the log includes expected data.
        $this->assertStringContainsString('ck-debug.INFO: GET account', $this->getLogFileContents());
        $this->assertStringContainsString('ck-debug.INFO: Finish request successfully', $this->getLogFileContents());
    }

    /**
     * Test that debug logging works when enabled, a custom debug log file and path is specified
     * and an API call is made.
     *
     * @since   2.2.0
     *
     * @return  void
     */
    public function testDebugEnabledWithCustomLogFile()
    {
        // Define custom log file location.
        $this->logFile = dirname(dirname(__FILE__)) . '/src/logs/debug-custom.log';

        // Setup API with debugging enabled.
        $api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET'],
            accessToken: $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
            debug: true,
            debugLogFileLocation: $this->logFile
        );
        $result = $api->get_account();

        // Confirm log file exists.
        $this->assertFileExists($this->logFile);

        // Confirm that the log includes expected data.
        $this->assertStringContainsString('ck-debug.INFO: GET account', $this->getLogFileContents());
        $this->assertStringContainsString('ck-debug.INFO: Finish request successfully', $this->getLogFileContents());
    }

    /**
     * Test that debug logging works when enabled and an API call is made, with email addresses and credentials
     * masked in the log file.
     *
     * @since   2.2.0
     *
     * @return  void
     */
    public function testDebugCredentialsAndEmailsAreMasked()
    {
        // Setup API with debugging enabled.
        $api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET'],
            accessToken: $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
            debug: true
        );

        // Create log entries with Client ID, Client Secret, Access Token and Email Address, as if an API method
        // were to log this sensitive data.
        $this->callPrivateMethod($api, 'create_log', ['Client ID: ' . $_ENV['CONVERTKIT_OAUTH_CLIENT_ID']]);
        $this->callPrivateMethod($api, 'create_log', ['Client Secret: ' . $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET']]);
        $this->callPrivateMethod($api, 'create_log', ['Access Token: ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN']]);
        $this->callPrivateMethod($api, 'create_log', ['Email: ' . $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']]);

        // Confirm that the log includes the masked Client ID, Secret, Access Token and Email Address.
        $this->assertStringContainsString(
            str_repeat(
                '*',
                (strlen($_ENV['CONVERTKIT_OAUTH_CLIENT_ID']) - 4)
            ) . substr($_ENV['CONVERTKIT_OAUTH_CLIENT_ID'], -4),
            $this->getLogFileContents()
        );
        $this->assertStringContainsString(
            str_repeat(
                '*',
                (strlen($_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET']) - 4)
            ) . substr($_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET'], -4),
            $this->getLogFileContents()
        );
        $this->assertStringContainsString(
            str_repeat(
                '*',
                (strlen($_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN']) - 4)
            ) . substr($_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'], -4),
            $this->getLogFileContents()
        );
        $this->assertStringContainsString(
            'o****@n********.c**',
            $this->getLogFileContents()
        );

        // Confirm that the log does not include the unmasked Client ID, Secret, Access Token or Email Address.
        $this->assertStringNotContainsString($_ENV['CONVERTKIT_OAUTH_CLIENT_ID'], $this->getLogFileContents());
        $this->assertStringNotContainsString($_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET'], $this->getLogFileContents());
        $this->assertStringNotContainsString($_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'], $this->getLogFileContents());
        $this->assertStringNotContainsString($_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL'], $this->getLogFileContents());
    }

    /**
     * Test that debug logging is not performed when disabled and an API call is made.
     *
     * @since   2.2.0
     *
     * @return  void
     */
    public function testDebugDisabled()
    {
        $result = $this->api->get_account();
        $this->assertEmpty($this->getLogFileContents());
    }

    /**
     * Test that calling request_headers() returns the expected array of headers
     *
     * @since   2.0.0
     *
     * @return  void
     */
    public function testRequestHeadersMethod()
    {
        $headers = $this->api->get_request_headers();
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals($headers['Accept'], 'application/json');
        $this->assertEquals($headers['Content-Type'], 'application/json; charset=utf-8');
        $this->assertEquals($headers['User-Agent'], 'ConvertKitPHPSDK/' . $this->api::VERSION . ';PHP/' . phpversion());
        $this->assertEquals($headers['Authorization'], 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN']);
    }

    /**
     * Test that calling request_headers() with a different `type` parameter
     * returns the expected array of headers
     *
     * @since   2.0.0
     *
     * @return  void
     */
    public function testRequestHeadersMethodWithType()
    {
        $headers = $this->api->get_request_headers(
            type: 'text/html'
        );
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals($headers['Accept'], 'text/html');
        $this->assertEquals($headers['Content-Type'], 'text/html; charset=utf-8');
        $this->assertEquals($headers['User-Agent'], 'ConvertKitPHPSDK/' . $this->api::VERSION . ';PHP/' . phpversion());
        $this->assertEquals($headers['Authorization'], 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN']);
    }

    /**
     * Test that calling request_headers() with the `auth` parameter set to false
     * returns the expected array of headers
     *
     * @since   2.0.0
     *
     * @return  void
     */
    public function testRequestHeadersMethodWithAuthDisabled()
    {
        $headers = $this->api->get_request_headers(
            auth: false
        );
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayNotHasKey('Authorization', $headers);
        $this->assertEquals($headers['Accept'], 'application/json');
        $this->assertEquals($headers['Content-Type'], 'application/json; charset=utf-8');
        $this->assertEquals($headers['User-Agent'], 'ConvertKitPHPSDK/' . $this->api::VERSION . ';PHP/' . phpversion());
    }

    /**
     * Test that calling request_headers() with a different `type` parameter
     * and the `auth` parameter set to false returns the expected array of headers
     *
     * @since   2.0.0
     *
     * @return  void
     */
    public function testRequestHeadersMethodWithTypeAndAuthDisabled()
    {
        $headers = $this->api->get_request_headers(
            type: 'text/html',
            auth: false
        );
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayNotHasKey('Authorization', $headers);
        $this->assertEquals($headers['Accept'], 'text/html');
        $this->assertEquals($headers['Content-Type'], 'text/html; charset=utf-8');
        $this->assertEquals($headers['User-Agent'], 'ConvertKitPHPSDK/' . $this->api::VERSION . ';PHP/' . phpversion());
    }

    /**
     * Test that get_oauth_url() returns the correct URL to begin the OAuth process.
     *
     * @since   2.0.0
     *
     * @return  void
     */
    public function testGetOAuthURL()
    {
        // Confirm the OAuth URL returned is correct.
        $this->assertEquals(
            $this->api->get_oauth_url($_ENV['CONVERTKIT_OAUTH_REDIRECT_URI']),
            'https://app.convertkit.com/oauth/authorize?' . http_build_query([
                'client_id' => $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
                'redirect_uri' => $_ENV['CONVERTKIT_OAUTH_REDIRECT_URI'],
                'response_type' => 'code',
            ])
        );
    }

    /**
     * Test that get_access_token() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetAccessToken()
    {
        // Initialize API.
        $api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET']
        );

        // Define response parameters.
        $params = [
            'access_token'  => 'example-access-token',
            'refresh_token' => 'example-refresh-token',
            'token_type'    => 'Bearer',
            'created_at'    => strtotime('now'),
            'expires_in'    => strtotime('+3 days'),
            'scope'         => 'public',
        ];

        // Add mock handler for this API request.
        $api = $this->mockResponse(
            api: $api,
            responseBody: $params,
        );

        // Send request.
        $result = $api->get_access_token(
            authCode: 'auth-code',
            redirectURI: $_ENV['CONVERTKIT_OAUTH_REDIRECT_URI'],
        );

        // Inspect response.
        $result = get_object_vars($result);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('scope', $result);
        $this->assertEquals($result['access_token'], $params['access_token']);
        $this->assertEquals($result['refresh_token'], $params['refresh_token']);
        $this->assertEquals($result['created_at'], $params['created_at']);
        $this->assertEquals($result['expires_in'], $params['expires_in']);
    }

    /**
     * Test that a ClientException is thrown when an invalid auth code is supplied
     * when fetching an access token.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetAccessTokenWithInvalidAuthCode()
    {
        $this->expectException(ClientException::class);
        $api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET']
        );
        $result = $api->get_access_token(
            authCode: 'not-a-real-auth-code',
            redirectURI: $_ENV['CONVERTKIT_OAUTH_REDIRECT_URI'],
        );
    }

    /**
     * Test that refresh_token() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testRefreshToken()
    {
        // Initialize API.
        $api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET']
        );

        // Define response parameters.
        $params = [
            'access_token'  => 'new-example-access-token',
            'refresh_token' => 'new-example-refresh-token',
            'token_type'    => 'Bearer',
            'created_at'    => strtotime('now'),
            'expires_in'    => strtotime('+3 days'),
            'scope'         => 'public',
        ];

        // Add mock handler for this API request.
        $api = $this->mockResponse(
            api: $api,
            responseBody: $params,
        );

        // Send request.
        $result = $api->refresh_token(
            refreshToken: 'refresh-token',
            redirectURI: $_ENV['CONVERTKIT_OAUTH_REDIRECT_URI'],
        );

        // Inspect response.
        $result = get_object_vars($result);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('scope', $result);
        $this->assertEquals($result['access_token'], $params['access_token']);
        $this->assertEquals($result['refresh_token'], $params['refresh_token']);
        $this->assertEquals($result['created_at'], $params['created_at']);
        $this->assertEquals($result['expires_in'], $params['expires_in']);
    }

    /**
     * Test that a ServerException is thrown when an invalid refresh token is supplied
     * when refreshing an access token.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testRefreshTokenWithInvalidToken()
    {
        $this->expectException(ServerException::class);
        $api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET']
        );
        $result = $api->refresh_token(
            refreshToken: 'not-a-real-refresh-token',
            redirectURI: $_ENV['CONVERTKIT_OAUTH_REDIRECT_URI'],
        );
    }

    /**
     * Test that a ClientException is thrown when an invalid access token is supplied.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testInvalidAPICredentials()
    {
        $this->expectException(ClientException::class);
        $api = new ConvertKit_API(
            clientID: 'fakeClientID',
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET'],
            accessToken: $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN']
        );
        $result = $api->get_account();

        $api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: 'fakeClientSecret',
            accessToken: $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN']
        );
        $result = $api->get_account();

        $api = new ConvertKit_API(
            clientID: $_ENV['CONVERTKIT_OAUTH_CLIENT_ID'],
            clientSecret: $_ENV['CONVERTKIT_OAUTH_CLIENT_SECRET'],
            accessToken: 'fakeAccessToken'
        );
        $result = $api->get_account();
    }
}
