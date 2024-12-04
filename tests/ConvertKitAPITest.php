<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Dotenv\Dotenv;
use ConvertKit_API\ConvertKit_API;

/**
 * ConvertKit API class tests.
 */
class ConvertKitAPITest extends TestCase
{
    /**
     * ConvertKit Class Object
     *
     * @var object
     */
    protected $api;

    /**
     * Location of the monologger log file.
     *
     * @since   1.2.0
     *
     * @var     string
     */
    protected $logFile = '';

    /**
     * Custom Field IDs to delete on teardown of a test.
     *
     * @since   2.0.0
     *
     * @var     array<int, int>
     */
    protected $custom_field_ids = [];

    /**
     * Subscriber IDs to unsubscribe on teardown of a test.
     *
     * @since   2.0.0
     *
     * @var     array<int, int>
     */
    protected $subscriber_ids = [];

    /**
     * Webhook IDs to delete on teardown of a test.
     *
     * @since   2.0.0
     *
     * @var     array<int, int>
     */
    protected $webhook_ids = [];

    /**
     * Broadcast IDs to delete on teardown of a test.
     *
     * @since   2.0.0
     *
     * @var     array<int, int>
     */
    protected $broadcast_ids = [];

    /**
     * Load .env configuration into $_ENV superglobal, and initialize the API
     * class before each test.
     *
     * @since   1.0.0
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
    }

    /**
     * Cleanup data from the ConvertKit account on a test pass/fail, such as unsubscribing, deleting custom fields etc
     *
     * @since   2.0.0
     *
     * @return  void
     */
    protected function tearDown(): void
    {
        // Delete any Custom Fields.
        foreach ($this->custom_field_ids as $id) {
            $this->api->delete_custom_field($id);
        }

        // Delete any Webhooks.
        foreach ($this->webhook_ids as $id) {
            $this->api->delete_webhook($id);
        }

        // Delete any Broadcasts.
        foreach ($this->broadcast_ids as $id) {
            $this->api->delete_broadcast($id);
        }
    }

    /**
     * Test that a Response instance is returned when calling getResponseInterface()
     * after making an API request.
     *
     * @since   2.0.0
     *
     * @return  void
     */
    public function testGetResponseInterface()
    {
        // Assert response interface is null, as no API request made.
        $this->assertNull($this->api->getResponseInterface());

        // Perform an API request.
        $result = $this->api->get_account();

        // Assert response interface is of a valid type.
        $this->assertInstanceOf(Response::class, $this->api->getResponseInterface());

        // Assert the correct status code was returned.
        $this->assertEquals(200, $this->api->getResponseInterface()->getStatusCode());
    }

    /**
     * Test that a ClientInterface can be injected.
     *
     * @since   1.3.0
     *
     * @return  void
     */
    public function testClientInterfaceInjection()
    {
        // Setup API with a mock Guzzle client.
        $mock = new MockHandler([
            new Response(200, [], json_encode(
                [
                    'name' => 'Test Account for Guzzle Mock',
                    'plan_type' => 'free',
                    'primary_email_address' => 'mock@guzzle.mock',
                ]
            )),
        ]);

        // Define client with mock handler.
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Assign the client to the API class.
        $this->api->set_http_client($client);

        // Perform an API request.
        $result = $this->api->get_account();

        // Confirm mocked data was returned.
        $this->assertSame('Test Account for Guzzle Mock', $result->name);
        $this->assertSame('free', $result->plan_type);
        $this->assertSame('mock@guzzle.mock', $result->primary_email_address);

        // Assert response interface is of a valid type when using `set_http_client`.
        $this->assertInstanceOf(Response::class, $this->api->getResponseInterface());

        // Assert the correct status code was returned.
        $this->assertEquals(200, $this->api->getResponseInterface()->getStatusCode());
    }

    /**
     * Test that debug logging works when enabled and an API call is made.
     *
     * @since   1.2.0
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
     * @since   1.3.0
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
     * @since   2.0.0
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
     * @since   1.2.0
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

    /**
     * Test that get_account() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetAccount()
    {
        $result = $this->api->get_account();
        $this->assertInstanceOf('stdClass', $result);

        $result = get_object_vars($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('account', $result);

        $account = get_object_vars($result['account']);
        $this->assertArrayHasKey('name', $account);
        $this->assertArrayHasKey('plan_type', $account);
        $this->assertArrayHasKey('primary_email_address', $account);
    }

    /**
     * Test that get_account_colors() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetAccountColors()
    {
        $result = $this->api->get_account_colors();
        $this->assertInstanceOf('stdClass', $result);

        $result = get_object_vars($result);
        $this->assertArrayHasKey('colors', $result);
        $this->assertIsArray($result['colors']);
    }

    /**
     * Test that update_account_colors() updates the account's colors.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testUpdateAccountColors()
    {
        $result = $this->api->update_account_colors([
            '#111111',
        ]);
        $this->assertInstanceOf('stdClass', $result);

        $result = get_object_vars($result);
        $this->assertArrayHasKey('colors', $result);
        $this->assertIsArray($result['colors']);
        $this->assertEquals($result['colors'][0], '#111111');
    }

    /**
     * Test that get_creator_profile() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetCreatorProfile()
    {
        $result = $this->api->get_creator_profile();
        $this->assertInstanceOf('stdClass', $result);

        $result = get_object_vars($result);
        $profile = get_object_vars($result['profile']);
        $this->assertArrayHasKey('name', $profile);
        $this->assertArrayHasKey('byline', $profile);
        $this->assertArrayHasKey('bio', $profile);
        $this->assertArrayHasKey('image_url', $profile);
        $this->assertArrayHasKey('profile_url', $profile);
    }

    /**
     * Test that get_email_stats() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetEmailStats()
    {
        $result = $this->api->get_email_stats();
        $this->assertInstanceOf('stdClass', $result);

        $result = get_object_vars($result);
        $stats = get_object_vars($result['stats']);
        $this->assertArrayHasKey('sent', $stats);
        $this->assertArrayHasKey('clicked', $stats);
        $this->assertArrayHasKey('opened', $stats);
        $this->assertArrayHasKey('email_stats_mode', $stats);
        $this->assertArrayHasKey('open_tracking_enabled', $stats);
        $this->assertArrayHasKey('click_tracking_enabled', $stats);
        $this->assertArrayHasKey('starting', $stats);
        $this->assertArrayHasKey('ending', $stats);
    }

    /**
     * Test that get_growth_stats() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetGrowthStats()
    {
        $result = $this->api->get_growth_stats();
        $this->assertInstanceOf('stdClass', $result);

        $result = get_object_vars($result);
        $stats = get_object_vars($result['stats']);
        $this->assertArrayHasKey('cancellations', $stats);
        $this->assertArrayHasKey('net_new_subscribers', $stats);
        $this->assertArrayHasKey('new_subscribers', $stats);
        $this->assertArrayHasKey('subscribers', $stats);
        $this->assertArrayHasKey('starting', $stats);
        $this->assertArrayHasKey('ending', $stats);
    }

    /**
     * Test that get_growth_stats() returns the expected data
     * when a start date is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetGrowthStatsWithStartDate()
    {
        // Define start and end dates.
        $starting = new DateTime('now');
        $starting->modify('-7 days');
        $ending = new DateTime('now');

        // Send request.
        $result = $this->api->get_growth_stats(
            starting: $starting
        );
        $this->assertInstanceOf('stdClass', $result);

        // Confirm response object contains expected keys.
        $result = get_object_vars($result);
        $stats = get_object_vars($result['stats']);
        $this->assertArrayHasKey('cancellations', $stats);
        $this->assertArrayHasKey('net_new_subscribers', $stats);
        $this->assertArrayHasKey('new_subscribers', $stats);
        $this->assertArrayHasKey('subscribers', $stats);
        $this->assertArrayHasKey('starting', $stats);
        $this->assertArrayHasKey('ending', $stats);

        // Assert start and end dates were honored.
        $this->assertEquals($stats['starting'], $starting->format('Y-m-d') . 'T00:00:00-05:00');
        $this->assertEquals($stats['ending'], $ending->format('Y-m-d') . 'T23:59:59-05:00');
    }

    /**
     * Test that get_growth_stats() returns the expected data
     * when an end date is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetGrowthStatsWithEndDate()
    {
        // Define start and end dates.
        $starting = new DateTime('now');
        $starting->modify('-90 days');
        $ending = new DateTime('now');
        $ending->modify('-7 days');

        // Send request.
        $result = $this->api->get_growth_stats(
            ending: $ending
        );
        $this->assertInstanceOf('stdClass', $result);

        // Confirm response object contains expected keys.
        $result = get_object_vars($result);
        $stats = get_object_vars($result['stats']);
        $this->assertArrayHasKey('cancellations', $stats);
        $this->assertArrayHasKey('net_new_subscribers', $stats);
        $this->assertArrayHasKey('new_subscribers', $stats);
        $this->assertArrayHasKey('subscribers', $stats);
        $this->assertArrayHasKey('starting', $stats);
        $this->assertArrayHasKey('ending', $stats);

        // Assert start and end dates were honored.
        $this->assertEquals($stats['starting'], $starting->format('Y-m-d') . 'T00:00:00-05:00');
        $this->assertEquals($stats['ending'], $ending->format('Y-m-d') . 'T23:59:59-05:00');
    }

    /**
     * Test that get_forms() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetForms()
    {
        $result = $this->api->get_forms();

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Iterate through each form, confirming no landing pages were included.
        foreach ($result->forms as $form) {
            $form = get_object_vars($form);

            // Assert shape of object is valid.
            $this->assertArrayHasKey('id', $form);
            $this->assertArrayHasKey('name', $form);
            $this->assertArrayHasKey('created_at', $form);
            $this->assertArrayHasKey('type', $form);
            $this->assertArrayHasKey('format', $form);
            $this->assertArrayHasKey('embed_js', $form);
            $this->assertArrayHasKey('embed_url', $form);
            $this->assertArrayHasKey('archived', $form);

            // Assert form is not a landing page i.e embed.
            $this->assertEquals($form['type'], 'embed');

            // Assert form is not archived.
            $this->assertFalse($form['archived']);
        }
    }

    /**
     * Test that get_forms() returns the expected data when
     * the status is set to archived.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormsWithArchivedStatus()
    {
        $result = $this->api->get_forms(
            status: 'archived'
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Iterate through each form, confirming no landing pages were included.
        foreach ($result->forms as $form) {
            $form = get_object_vars($form);

            // Assert shape of object is valid.
            $this->assertArrayHasKey('id', $form);
            $this->assertArrayHasKey('name', $form);
            $this->assertArrayHasKey('created_at', $form);
            $this->assertArrayHasKey('type', $form);
            $this->assertArrayHasKey('format', $form);
            $this->assertArrayHasKey('embed_js', $form);
            $this->assertArrayHasKey('embed_url', $form);
            $this->assertArrayHasKey('archived', $form);

            // Assert form is not a landing page i.e embed.
            $this->assertEquals($form['type'], 'embed');

            // Assert form is not archived.
            $this->assertTrue($form['archived']);
        }
    }

    /**
     * Test that get_forms() returns the expected data
     * when the total count is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormsWithTotalCount()
    {
        $result = $this->api->get_forms(
            include_total_count: true
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_forms() returns the expected data when pagination parameters
     * and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormsPagination()
    {
        $result = $this->api->get_forms(
            per_page: 1
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Assert a single form was returned.
        $this->assertCount(1, $result->forms);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_forms(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Assert a single form was returned.
        $this->assertCount(1, $result->forms);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_forms(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Assert a single form was returned.
        $this->assertCount(1, $result->forms);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);
    }

    /**
     * Test that get_landing_pages() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetLandingPages()
    {
        $result = $this->api->get_landing_pages();

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Iterate through each landing page, confirming no forms were included.
        foreach ($result->forms as $form) {
            $form = get_object_vars($form);

            // Assert shape of object is valid.
            $this->assertArrayHasKey('id', $form);
            $this->assertArrayHasKey('name', $form);
            $this->assertArrayHasKey('created_at', $form);
            $this->assertArrayHasKey('type', $form);
            $this->assertArrayHasKey('format', $form);
            $this->assertArrayHasKey('embed_js', $form);
            $this->assertArrayHasKey('embed_url', $form);
            $this->assertArrayHasKey('archived', $form);

            // Assert form is a landing page i.e. hosted.
            $this->assertEquals($form['type'], 'hosted');

            // Assert form is not archived.
            $this->assertFalse($form['archived']);
        }
    }

    /**
     * Test that get_landing_pages() returns the expected data when
     * the status is set to archived.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetLandingPagesWithArchivedStatus()
    {
        $result = $this->api->get_forms(
            status: 'archived'
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Assert no landing pages are returned, as the account doesn't have any archived landing pages.
        $this->assertCount(0, $result->forms);
    }

    /**
     * Test that get_landing_pages() returns the expected data
     * when the total count is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetLandingPagesWithTotalCount()
    {
        $result = $this->api->get_landing_pages(
            include_total_count: true
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_landing_pages() returns the expected data when pagination parameters
     * and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetLandingPagesPagination()
    {
        $result = $this->api->get_landing_pages(
            per_page: 1
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Assert a single form was returned.
        $this->assertCount(1, $result->forms);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_landing_pages(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Assert a single form was returned.
        $this->assertCount(1, $result->forms);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertFalse($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_landing_pages(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert forms and pagination exist.
        $this->assertDataExists($result, 'forms');
        $this->assertPaginationExists($result);

        // Assert a single form was returned.
        $this->assertCount(1, $result->forms);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);
    }

    /**
     * Test that get_form_subscriptions() returns the expected data
     * when a valid Form ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptions()
    {
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID']
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_form_subscriptions() returns the expected data
     * when the total count is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsWithTotalCount()
    {
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            include_total_count: true
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_form_subscriptions() returns the expected data
     * when a valid Form ID is specified and the subscription status
     * is cancelled.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsWithBouncedSubscriberState()
    {
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            subscriber_state: 'bounced'
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertEquals($result->subscribers[0]->state, 'bounced');
    }

    /**
     * Test that get_form_subscriptions() returns the expected data
     * when a valid Form ID is specified and the added_after parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsWithAddedAfterParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            added_after: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertGreaterThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->added_at))
        );
    }

    /**
     * Test that get_form_subscriptions() returns the expected data
     * when a valid Form ID is specified and the added_before parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsWithAddedBeforeParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            added_before: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertLessThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->added_at))
        );
    }

    /**
     * Test that get_form_subscriptions() returns the expected data
     * when a valid Form ID is specified and the created_after parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsWithCreatedAfterParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            created_after: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertGreaterThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->created_at))
        );
    }

    /**
     * Test that get_form_subscriptions() returns the expected data
     * when a valid Form ID is specified and the created_before parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsWithCreatedBeforeParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            created_before: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertLessThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->created_at))
        );
    }

    /**
     * Test that get_form_subscriptions() returns the expected data
     * when a valid Form ID is specified and pagination parameters
     * and per_page limits are specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsPagination()
    {
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            per_page: 1
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert a single subscriber was returned.
        $this->assertCount(1, $result->subscribers);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert a single subscriber was returned.
        $this->assertCount(1, $result->subscribers);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_form_subscriptions() throws a ClientException when an invalid
     * Form ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsWithInvalidFormID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_form_subscriptions(
            form_id: 12345
        );
    }

    /**
     * Test that get_form_subscriptions() throws a ClientException when an invalid
     * subscriber state is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsWithInvalidSubscriberState()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            subscriber_state: 'not-a-valid-state'
        );
    }

    /**
     * Test that get_form_subscriptions() throws a ClientException when invalid
     * pagination parameters are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetFormSubscriptionsWithInvalidPagination()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_form_subscriptions(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            after_cursor: 'not-a-valid-cursor'
        );
    }

    /**
     * Test that get_sequences() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSequences()
    {
        $result = $this->api->get_sequences();

        // Assert sequences and pagination exist.
        $this->assertDataExists($result, 'sequences');
        $this->assertPaginationExists($result);

        // Check first sequence in resultset has expected data.
        $sequence = get_object_vars($result->sequences[0]);
        $this->assertArrayHasKey('id', $sequence);
        $this->assertArrayHasKey('name', $sequence);
        $this->assertArrayHasKey('hold', $sequence);
        $this->assertArrayHasKey('repeat', $sequence);
        $this->assertArrayHasKey('created_at', $sequence);
    }

    /**
     * Test that get_sequences() returns the expected data
     * when the total count is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSequencesWithTotalCount()
    {
        $result = $this->api->get_sequences(
            include_total_count: true
        );

        // Assert sequences and pagination exist.
        $this->assertDataExists($result, 'sequences');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_sequences() returns the expected data when
     * pagination parameters and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSequencesPagination()
    {
        $result = $this->api->get_sequences(
            per_page: 1
        );

        // Assert sequences and pagination exist.
        $this->assertDataExists($result, 'sequences');
        $this->assertPaginationExists($result);

        // Assert a single sequence was returned.
        $this->assertCount(1, $result->sequences);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_sequences(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert sequences and pagination exist.
        $this->assertDataExists($result, 'sequences');
        $this->assertPaginationExists($result);

        // Assert a single sequence was returned.
        $this->assertCount(1, $result->sequences);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertFalse($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_sequences(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert sequences and pagination exist.
        $this->assertDataExists($result, 'sequences');
        $this->assertPaginationExists($result);

        // Assert a single sequence was returned.
        $this->assertCount(1, $result->sequences);
    }

    /**
     * Test that add_subscriber_to_sequence_by_email() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testAddSubscriberToSequenceByEmail()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $subscriber = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $subscriber->subscriber->id;

        // Add subscriber to sequence.
        $result = $this->api->add_subscriber_to_sequence_by_email(
            sequence_id: $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            email_address: $emailAddress
        );
        $this->assertInstanceOf('stdClass', $result);
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertEquals(
            get_object_vars($result->subscriber)['email_address'],
            $emailAddress
        );
    }

    /**
     * Test that add_subscriber_to_sequence_by_email() throws a ClientException when an invalid
     * sequence is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testAddSubscriberToSequenceByEmailWithInvalidSequenceID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->add_subscriber_to_sequence_by_email(
            sequence_id: 12345,
            email_address: $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']
        );
    }

    /**
     * Test that add_subscriber_to_sequence_by_email() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testAddSubscriberToSequenceByEmailWithInvalidEmailAddress()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->add_subscriber_to_sequence_by_email(
            sequence_id: $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            email_address: 'not-an-email-address'
        );
    }

    /**
     * Test that add_subscriber_to_sequence() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testAddSubscriberToSequence()
    {
        // Create subscriber.
        $subscriber = $this->api->create_subscriber(
            email_address: $this->generateEmailAddress()
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $subscriber->subscriber->id;

        // Add subscriber to sequence.
        $result = $this->api->add_subscriber_to_sequence(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            subscriber_id: $subscriber->subscriber->id
        );
        $this->assertInstanceOf('stdClass', $result);
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertEquals(get_object_vars($result->subscriber)['id'], $subscriber->subscriber->id);
    }

    /**
     * Test that add_subscriber_to_sequence() throws a ClientException when an invalid
     * sequence ID is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testAddSubscriberToSequenceWithInvalidSequenceID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->add_subscriber_to_sequence(
            sequence_id: 12345,
            subscriber_id: $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']
        );
    }

    /**
     * Test that add_subscriber_to_sequence() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testAddSubscriberToSequenceWithInvalidSubscriberID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->add_subscriber_to_sequence(
            sequence_id: $_ENV['CONVERTKIT_API_SUBSCRIBER_ID'],
            subscriber_id: 12345
        );
    }

    /**
     * Test that get_sequence_subscriptions() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptions()
    {
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: $_ENV['CONVERTKIT_API_SEQUENCE_ID']
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_sequence_subscriptions() returns the expected data
     * when the total count is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsWithTotalCount()
    {
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            include_total_count: true
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_sequence_subscriptions() returns the expected data
     * when a valid Sequence ID is specified and the subscription status
     * is cancelled.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsWithBouncedSubscriberState()
    {
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            subscriber_state: 'bounced'
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertEquals($result->subscribers[0]->state, 'bounced');
    }

    /**
     * Test that get_sequence_subscriptions() returns the expected data
     * when a valid Sequence ID is specified and the added_after parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsWithAddedAfterParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            added_after: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertGreaterThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->added_at))
        );
    }

    /**
     * Test that get_sequence_subscriptions() returns the expected data
     * when a valid Sequence ID is specified and the added_before parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsWithAddedBeforeParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            added_before: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertLessThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->added_at))
        );
    }

    /**
     * Test that get_sequence_subscriptions() returns the expected data
     * when a valid Sequence ID is specified and the created_after parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsWithCreatedAfterParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            created_after: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertGreaterThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->created_at))
        );
    }

    /**
     * Test that get_sequence_subscriptions() returns the expected data
     * when a valid Sequence ID is specified and the created_before parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsWithCreatedBeforeParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            created_before: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertLessThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->created_at))
        );
    }

    /**
     * Test that get_sequence_subscriptions() returns the expected data
     * when a valid Sequence ID is specified and pagination parameters
     * and per_page limits are specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsPagination()
    {
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            per_page: 1
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert a single subscriber was returned.
        $this->assertCount(1, $result->subscribers);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert a single subscriber was returned.
        $this->assertCount(1, $result->subscribers);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_sequence_subscriptions() throws a ClientException when an invalid
     * Sequence ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsWithInvalidSequenceID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: 12345
        );
    }

    /**
     * Test that get_sequence_subscriptions() throws a ClientException when an invalid
     * subscriber state is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsWithInvalidSubscriberState()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            subscriber_state: 'not-a-valid-state'
        );
    }

    /**
     * Test that get_sequence_subscriptions() throws a ClientException when invalid
     * pagination parameters are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSequenceSubscriptionsWithInvalidPagination()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_sequence_subscriptions(
            sequence_id: (int) $_ENV['CONVERTKIT_API_SEQUENCE_ID'],
            after_cursor: 'not-a-valid-cursor'
        );
    }

    /**
     * Test that get_tags() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetTags()
    {
        $result = $this->api->get_tags();

        // Assert sequences and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Check first tag in resultset has expected data.
        $tag = get_object_vars($result->tags[0]);
        $this->assertArrayHasKey('id', $tag);
        $this->assertArrayHasKey('name', $tag);
        $this->assertArrayHasKey('created_at', $tag);
    }

    /**
     * Test that get_tags() returns the expected data
     * when the total count is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetTagsWithTotalCount()
    {
        $result = $this->api->get_tags(
            include_total_count: true
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_tags() returns the expected data
     * when pagination parameters and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetTagsPagination()
    {
        $result = $this->api->get_tags(
            per_page: 1
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Assert a single tag was returned.
        $this->assertCount(1, $result->tags);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_tags(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Assert a single subscriber was returned.
        $this->assertCount(1, $result->tags);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_tags(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that create_tag() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateTag()
    {
        $tagName = 'Tag Test ' . mt_rand();

        // Add mock handler for this API request, as the API doesn't provide
        // a method to delete tags to cleanup the test.
        $this->api = $this->mockResponse(
            api: $this->api,
            responseBody: [
                'tag' => [
                    'id' => 12345,
                    'name' => $tagName,
                    'created_at' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
                ],
            ]
        );

        // Send request.
        $result = $this->api->create_tag($tagName);

        // Assert response contains correct data.
        $tag = get_object_vars($result->tag);
        $this->assertArrayHasKey('id', $tag);
        $this->assertArrayHasKey('name', $tag);
        $this->assertArrayHasKey('created_at', $tag);
        $this->assertEquals($tag['name'], $tagName);
    }

    /**
     * Test that create_tag() throws a ClientException when creating
     * a blank tag.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateTagBlank()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->create_tag('');
    }

    /**
     * Test that create_tag() throws a ClientException when creating
     * a tag that already exists.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateTagThatExists()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->create_tag($_ENV['CONVERTKIT_API_TAG_NAME']);
    }

    /**
     * Test that create_tags() returns the expected data.
     *
     * @since   1.1.0
     *
     * @return void
     */
    public function testCreateTags()
    {
        $tagNames = [
            'Tag Test ' . mt_rand(),
            'Tag Test ' . mt_rand(),
        ];

        // Add mock handler for this API request, as the API doesn't provide
        // a method to delete tags to cleanup the test.
        $this->api = $this->mockResponse(
            api: $this->api,
            responseBody: [
                'tags' => [
                    [
                        'id' => 12345,
                        'name' => $tagNames[0],
                        'created_at' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
                    ],
                    [
                        'id' => 23456,
                        'name' => $tagNames[1],
                        'created_at' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
                    ],
                ],
                'failures' => [],
            ]
        );

        $result = $this->api->create_tags($tagNames);

        // Assert no failures.
        $this->assertCount(0, $result->failures);
    }

    /**
     * Test that create_tags() returns failures when attempting
     * to create blank tags.
     *
     * @since   1.1.0
     *
     * @return void
     */
    public function testCreateTagsBlank()
    {
        $result = $this->api->create_tags([
            '',
            '',
        ]);

        // Assert failures.
        $this->assertCount(2, $result->failures);
    }

    /**
     * Test that create_tags() throws a ClientException when creating
     * tags that already exists.
     *
     * @since   1.1.0
     *
     * @return void
     */
    public function testCreateTagsThatExist()
    {
        $result = $this->api->create_tags([
            $_ENV['CONVERTKIT_API_TAG_NAME'],
            $_ENV['CONVERTKIT_API_TAG_NAME_2'],
        ]);

        // Assert failures.
        $this->assertCount(2, $result->failures);
    }

    /**
     * Test that tag_subscriber_by_email() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testTagSubscriberByEmail()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Tag subscriber by email.
        $subscriber = $this->api->tag_subscriber_by_email(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            email_address: $emailAddress,
        );
        $this->assertArrayHasKey('subscriber', get_object_vars($subscriber));
        $this->assertArrayHasKey('id', get_object_vars($subscriber->subscriber));
        $this->assertArrayHasKey('tagged_at', get_object_vars($subscriber->subscriber));

        // Confirm the subscriber is tagged.
        $result = $this->api->get_subscriber_tags(
            subscriber_id: $subscriber->subscriber->id
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Assert correct tag was assigned.
        $this->assertEquals($result->tags[0]->id, $_ENV['CONVERTKIT_API_TAG_ID']);
    }

    /**
     * Test that tag_subscriber_by_email() throws a ClientException when an invalid
     * tag is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testTagSubscriberByEmailWithInvalidTagID()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $this->api->create_subscriber(
            email_address: $emailAddress
        );

        $this->expectException(ClientException::class);
        $result = $this->api->tag_subscriber_by_email(
            tag_id: 12345,
            email_address: $emailAddress
        );
    }

    /**
     * Test that tag_subscriber_by_email() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testTagSubscriberByEmailWithInvalidEmailAddress()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->tag_subscriber_by_email(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            email_address: 'not-an-email-address'
        );
    }

    /**
     * Test that tag_subscriber() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testTagSubscriber()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $subscriber = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Tag subscriber by email.
        $result = $this->api->tag_subscriber(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            subscriber_id: $subscriber->subscriber->id,
        );
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertArrayHasKey('tagged_at', get_object_vars($result->subscriber));

        // Confirm the subscriber is tagged.
        $result = $this->api->get_subscriber_tags(
            subscriber_id: $result->subscriber->id
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Assert correct tag was assigned.
        $this->assertEquals($result->tags[0]->id, $_ENV['CONVERTKIT_API_TAG_ID']);
    }

    /**
     * Test that tag_subscriber() throws a ClientException when an invalid
     * sequence ID is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testTagSubscriberWithInvalidTagID()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $subscriber = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        $this->expectException(ClientException::class);
        $result = $this->api->tag_subscriber(
            tag_id: 12345,
            subscriber_id: $subscriber->subscriber->id
        );
    }

    /**
     * Test that tag_subscriber() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testTagSubscriberWithInvalidSubscriberID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->tag_subscriber(
            tag_id: $_ENV['CONVERTKIT_API_TAG_ID'],
            subscriber_id: 12345
        );
    }

    /**
     * Test that remove_tag_from_subscriber() works.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testRemoveTagFromSubscriber()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Tag subscriber by email.
        $subscriber = $this->api->tag_subscriber_by_email(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            email_address: $emailAddress,
        );

        // Remove tag from subscriber.
        $result = $this->api->remove_tag_from_subscriber(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            subscriber_id: $subscriber->subscriber->id
        );

        // Confirm that the subscriber no longer has the tag.
        $result = $this->api->get_subscriber_tags($subscriber->subscriber->id);
        $this->assertIsArray($result->tags);
        $this->assertCount(0, $result->tags);
    }

    /**
     * Test that remove_tag_from_subscriber() throws a ClientException when an invalid
     * tag ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testRemoveTagFromSubscriberWithInvalidTagID()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Tag subscriber by email.
        $subscriber = $this->api->tag_subscriber_by_email(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            email_address: $emailAddress,
        );

        // Remove tag from subscriber.
        $this->expectException(ClientException::class);
        $result = $this->api->remove_tag_from_subscriber(
            tag_id: 12345,
            subscriber_id: $subscriber->subscriber->id
        );
    }

    /**
     * Test that remove_tag_from_subscriber() throws a ClientException when an invalid
     * subscriber ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testRemoveTagFromSubscriberWithInvalidSubscriberID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->remove_tag_from_subscriber(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            subscriber_id: 12345
        );
    }

    /**
     * Test that remove_tag_from_subscriber() works.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testRemoveTagFromSubscriberByEmail()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Tag subscriber by email.
        $subscriber = $this->api->tag_subscriber_by_email(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            email_address: $emailAddress,
        );

        // Remove tag from subscriber.
        $result = $this->api->remove_tag_from_subscriber(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            subscriber_id: $subscriber->subscriber->id
        );

        // Confirm that the subscriber no longer has the tag.
        $result = $this->api->get_subscriber_tags($subscriber->subscriber->id);
        $this->assertIsArray($result->tags);
        $this->assertCount(0, $result->tags);
    }

    /**
     * Test that remove_tag_from_subscriber() throws a ClientException when an invalid
     * tag ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testRemoveTagFromSubscriberByEmailWithInvalidTagID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->remove_tag_from_subscriber_by_email(
            tag_id: 12345,
            email_address: $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']
        );
    }

    /**
     * Test that remove_tag_from_subscriber() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testRemoveTagFromSubscriberByEmailWithInvalidEmailAddress()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->remove_tag_from_subscriber_by_email(
            tag_id: $_ENV['CONVERTKIT_API_TAG_ID'],
            email_address: 'not-an-email-address'
        );
    }

    /**
     * Test that get_tag_subscriptions() returns the expected data
     * when a valid Tag ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetTagSubscriptions()
    {
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID']
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_tag_subscriptions() returns the expected data
     * when the total count is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetTagSubscriptionsWithTotalCount()
    {
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            include_total_count: true
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_tag_subscriptions() returns the expected data
     * when a valid Tag ID is specified and the subscription status
     * is bounced.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetTagSubscriptionsWithBouncedSubscriberState()
    {
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            subscriber_state: 'bounced'
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertEquals($result->subscribers[0]->state, 'bounced');
    }


    /**
     * Test that get_tag_subscriptions() returns the expected data
     * when a valid Tag ID is specified and the added_after parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetTagSubscriptionsWithTaggedAfterParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            tagged_after: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertGreaterThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->tagged_at))
        );
    }

    /**
     * Test that get_tag_subscriptions() returns the expected data
     * when a valid Tag ID is specified and the tagged_before parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetTagSubscriptionsWithTaggedBeforeParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            tagged_before: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertLessThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->tagged_at))
        );
    }

    /**
     * Test that get_tag_subscriptions() returns the expected data
     * when a valid Tag ID is specified and the created_after parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetTagSubscriptionsWithCreatedAfterParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            created_after: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertGreaterThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->created_at))
        );
    }

    /**
     * Test that get_tag_subscriptions() returns the expected data
     * when a valid Tag ID is specified and the created_before parameter
     * is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetTagSubscriptionsWithCreatedBeforeParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            created_before: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertLessThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->created_at))
        );
    }

    /**
     * Test that get_tag_subscriptions() returns the expected data
     * when a valid Tag ID is specified and pagination parameters
     * and per_page limits are specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetTagSubscriptionsPagination()
    {
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            per_page: 1
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert a single subscriber was returned.
        $this->assertCount(1, $result->subscribers);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert a single subscriber was returned.
        $this->assertCount(1, $result->subscribers);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_tag_subscriptions(
            tag_id: (int) $_ENV['CONVERTKIT_API_TAG_ID'],
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_tag_subscriptions() returns the expected data
     * when a valid Tag ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetTagSubscriptionsWithInvalidTagID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_tag_subscriptions(12345);
    }

    /**
     * Test that add_subscriber_to_form_by_email() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testAddSubscriberToFormByEmail()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $subscriber = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $subscriber->subscriber->id;

        // Add subscriber to form.
        $result = $this->api->add_subscriber_to_form_by_email(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            email_address: $emailAddress
        );
        $this->assertInstanceOf('stdClass', $result);
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertEquals(
            get_object_vars($result->subscriber)['email_address'],
            $emailAddress
        );
    }

    /**
     * Test that add_subscriber_to_form_by_email() returns the expected data
     * when a referrer is specified.
     *
     * @since   2.0.1
     *
     * @return void
     */
    public function testAddSubscriberToFormByEmailWithReferrer()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $subscriber = $this->api->create_subscriber(
            email_address: $emailAddress,
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $subscriber->subscriber->id;

        // Add subscriber to form.
        $result = $this->api->add_subscriber_to_form_by_email(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            email_address: $emailAddress,
            referrer: 'https://example.com',
        );

        $this->assertInstanceOf('stdClass', $result);
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertEquals(
            get_object_vars($result->subscriber)['email_address'],
            $emailAddress
        );
        $this->assertEquals(
            $result->subscriber->referrer,
            'https://example.com'
        );
    }

    /**
     * Test that add_subscriber_to_form_by_email() returns the expected data
     * when a referrer is specified that includes UTM parameters.
     *
     * @since   2.0.1
     *
     * @return void
     */
    public function testAddSubscriberToFormByEmailWithReferrerUTMParams()
    {
        // Define referrer.
        $referrerUTMParams = [
            'utm_source'    => 'source',
            'utm_medium'    => 'medium',
            'utm_campaign'  => 'campaign',
            'utm_term'      => 'term',
            'utm_content'   => 'content',
        ];
        $referrer = 'https://example.com/?' . http_build_query($referrerUTMParams);

        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $subscriber = $this->api->create_subscriber(
            email_address: $emailAddress,
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $subscriber->subscriber->id;

        // Add subscriber to form.
        $result = $this->api->add_subscriber_to_form_by_email(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            email_address: $emailAddress,
            referrer: $referrer,
        );

        var_dump( $result );
        die();

        $this->assertInstanceOf('stdClass', $result);
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertEquals(
            get_object_vars($result->subscriber)['email_address'],
            $emailAddress
        );
        $this->assertEquals(
            $result->subscriber->referrer,
            $referrer
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->source,
            $referrerUTMParams['utm_source']
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->medium,
            $referrerUTMParams['utm_medium']
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->campaign,
            $referrerUTMParams['utm_campaign']
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->term,
            $referrerUTMParams['utm_term']
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->content,
            $referrerUTMParams['utm_content']
        );
    }

    /**
     * Test that add_subscriber_to_form_by_email() throws a ClientException when an invalid
     * form ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testAddSubscriberToFormByEmailWithInvalidFormID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->add_subscriber_to_form_by_email(
            form_id: 12345,
            email_address: $this->generateEmailAddress()
        );
    }

    /**
     * Test that add_subscriber_to_form() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testAddSubscriberToFormByEmailWithInvalidEmailAddress()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->add_subscriber_to_form_by_email(
            form_id: $_ENV['CONVERTKIT_API_FORM_ID'],
            email_address: 'not-an-email-address'
        );
    }

    /**
     * Test that add_subscriber_to_form() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testAddSubscriberToForm()
    {
        // Create subscriber.
        $subscriber = $this->api->create_subscriber(
            email_address: $this->generateEmailAddress()
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $subscriber->subscriber->id;

        $result = $this->api->add_subscriber_to_form(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            subscriber_id: $subscriber->subscriber->id
        );
        $this->assertInstanceOf('stdClass', $result);
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertEquals(get_object_vars($result->subscriber)['id'], $subscriber->subscriber->id);
    }

    /**
     * Test that add_subscriber_to_form() returns the expected data
     * when a referrer is specified.
     *
     * @since   2.0.1
     *
     * @return void
     */
    public function testAddSubscriberToFormWithReferrer()
    {
        // Create subscriber.
        $subscriber = $this->api->create_subscriber(
            email_address: $this->generateEmailAddress()
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $subscriber->subscriber->id;

        $result = $this->api->add_subscriber_to_form(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            subscriber_id: $subscriber->subscriber->id,
            referrer: 'https://example.com',
        );
        $this->assertInstanceOf('stdClass', $result);
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertEquals(get_object_vars($result->subscriber)['id'], $subscriber->subscriber->id);
        $this->assertEquals(
            $result->subscriber->referrer,
            'https://example.com'
        );
    }

    /**
     * Test that add_subscriber_to_form() returns the expected data
     * when a referrer is specified that includes UTM parameters.
     *
     * @since   2.0.1
     *
     * @return void
     */
    public function testAddSubscriberToFormWithReferrerUTMParams()
    {
        // Define referrer.
        $referrerUTMParams = [
            'utm_source'    => 'source',
            'utm_medium'    => 'medium',
            'utm_campaign'  => 'campaign',
            'utm_term'      => 'term',
            'utm_content'   => 'content',
        ];
        $referrer = 'https://example.com/?' . http_build_query($referrerUTMParams);

        // Create subscriber.
        $subscriber = $this->api->create_subscriber(
            email_address: $this->generateEmailAddress()
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $subscriber->subscriber->id;

        $result = $this->api->add_subscriber_to_form(
            form_id: (int) $_ENV['CONVERTKIT_API_FORM_ID'],
            subscriber_id: $subscriber->subscriber->id,
            referrer: $referrer,
        );

        $this->assertInstanceOf('stdClass', $result);
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertEquals(get_object_vars($result->subscriber)['id'], $subscriber->subscriber->id);

        $this->assertInstanceOf('stdClass', $result);
        $this->assertArrayHasKey('subscriber', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->subscriber));
        $this->assertEquals(
            $result->subscriber->referrer,
            $referrer
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->source,
            $referrerUTMParams['utm_source']
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->medium,
            $referrerUTMParams['utm_medium']
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->campaign,
            $referrerUTMParams['utm_campaign']
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->term,
            $referrerUTMParams['utm_term']
        );
        $this->assertEquals(
            $result->subscriber->referrer_utm_parameters->content,
            $referrerUTMParams['utm_content']
        );
    }

    /**
     * Test that add_subscriber_to_form() throws a ClientException when an invalid
     * form ID is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testAddSubscriberToFormWithInvalidFormID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->add_subscriber_to_form(
            form_id: 12345,
            subscriber_id: $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']
        );
    }

    /**
     * Test that add_subscriber_to_form() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testAddSubscriberToFormWithInvalidSubscriberID()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->add_subscriber_to_form(
            form_id: $_ENV['CONVERTKIT_API_FORM_ID'],
            subscriber_id: 12345
        );
    }

    /**
     * Test that get_subscribers() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribers()
    {
        $result = $this->api->get_subscribers();

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_subscribers() returns the expected data
     * when the total count is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithTotalCount()
    {
        $result = $this->api->get_subscribers(
            include_total_count: true
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_subscribers() returns the expected data when
     * searching by an email address.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersByEmailAddress()
    {
        $result = $this->api->get_subscribers(
            email_address: $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert correct subscriber returned.
        $this->assertEquals(
            $result->subscribers[0]->email_address,
            $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']
        );
    }

    /**
     * Test that get_subscribers() returns the expected data
     * when the subscription status is bounced.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithBouncedSubscriberState()
    {
        $result = $this->api->get_subscribers(
            subscriber_state: 'bounced'
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertEquals($result->subscribers[0]->state, 'bounced');
    }

    /**
     * Test that get_subscribers() returns the expected data
     * when the created_after parameter is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithCreatedAfterParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_subscribers(
            created_after: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertGreaterThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->created_at))
        );
    }

    /**
     * Test that get_subscribers() returns the expected data
     * when the created_before parameter is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithCreatedBeforeParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_subscribers(
            created_before: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Check the correct subscribers were returned.
        $this->assertLessThanOrEqual(
            $date->format('Y-m-d'),
            date('Y-m-d', strtotime($result->subscribers[0]->created_at))
        );
    }

    /**
     * Test that get_subscribers() returns the expected data
     * when the updated_after parameter is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithUpdatedAfterParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_subscribers(
            updated_after: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_subscribers() returns the expected data
     * when the updated_before parameter is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithUpdatedBeforeParam()
    {
        $date = new \DateTime('2024-01-01');
        $result = $this->api->get_subscribers(
            updated_before: $date
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_subscribers() returns the expected data
     * when the sort_field parameter is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithSortFieldParam()
    {
        $result = $this->api->get_subscribers(
            sort_field: 'id'
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert sorting is honored by ID in descending (default) order.
        $this->assertLessThanOrEqual(
            $result->subscribers[0]->id,
            $result->subscribers[1]->id
        );
    }

    /**
     * Test that get_subscribers() returns the expected data
     * when the sort_order parameter is used.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithSortOrderParam()
    {
        $result = $this->api->get_subscribers(
            sort_order: 'asc'
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert sorting is honored by ID (default) in ascending order.
        $this->assertGreaterThanOrEqual(
            $result->subscribers[0]->id,
            $result->subscribers[1]->id
        );
    }

    /**
     * Test that get_subscribers() returns the expected data
     * when pagination parameters and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersPagination()
    {
        $result = $this->api->get_subscribers(
            per_page: 1
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert a single subscriber was returned.
        $this->assertCount(1, $result->subscribers);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_subscribers(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);

        // Assert a single subscriber was returned.
        $this->assertCount(1, $result->subscribers);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_subscribers(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert subscribers and pagination exist.
        $this->assertDataExists($result, 'subscribers');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_subscribers() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithInvalidEmailAddress()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_subscribers(
            email_address: 'not-an-email-address'
        );
    }

    /**
     * Test that get_subscribers() throws a ClientException when an invalid
     * subscriber state is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithInvalidSubscriberState()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_subscribers(
            subscriber_state: 'not-an-valid-state'
        );
    }

    /**
     * Test that get_subscribers() throws a ClientException when an invalid
     * sort field is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithInvalidSortFieldParam()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_subscribers(
            sort_field: 'not-a-valid-sort-field'
        );
    }

    /**
     * Test that get_subscribers() throws a ClientException when an invalid
     * sort order is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithInvalidSortOrderParam()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_subscribers(
            sort_order: 'not-a-valid-sort-order'
        );
    }

    /**
     * Test that get_subscribers() throws a ClientException when an invalid
     * pagination parameters are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscribersWithInvalidPagination()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_subscribers(
            after_cursor: 'not-a-valid-cursor'
        );
    }

    /**
     * Test that create_subscriber() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscriber()
    {
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $result->subscriber->id;

        // Assert subscriber exists with correct data.
        $this->assertEquals($result->subscriber->email_address, $emailAddress);
    }

    /**
     * Test that create_subscriber() returns the expected data
     * when a first name is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscriberWithFirstName()
    {
        $firstName = 'FirstName';
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress,
            first_name: $firstName
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $result->subscriber->id;

        // Assert subscriber exists with correct data.
        $this->assertEquals($result->subscriber->email_address, $emailAddress);
        $this->assertEquals($result->subscriber->first_name, $firstName);
    }

    /**
     * Test that create_subscriber() returns the expected data
     * when a subscriber state is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscriberWithSubscriberState()
    {
        $subscriberState = 'cancelled';
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress,
            subscriber_state: $subscriberState
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $result->subscriber->id;

        // Assert subscriber exists with correct data.
        $this->assertEquals($result->subscriber->email_address, $emailAddress);
        $this->assertEquals($result->subscriber->state, $subscriberState);
    }

    /**
     * Test that create_subscriber() returns the expected data
     * when custom field data is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscriberWithCustomFields()
    {
        $lastName = 'LastName';
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress,
            fields: [
                'last_name' => $lastName
            ]
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $result->subscriber->id;

        // Assert subscriber exists with correct data.
        $this->assertEquals($result->subscriber->email_address, $emailAddress);
        $this->assertEquals($result->subscriber->fields->last_name, $lastName);
    }

    /**
     * Test that create_subscriber() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscriberWithInvalidEmailAddress()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->create_subscriber(
            email_address: 'not-an-email-address'
        );
    }

    /**
     * Test that create_subscriber() throws a ClientException when an invalid
     * subscriber state is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscriberWithInvalidSubscriberState()
    {
        $this->expectException(ClientException::class);
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress,
            subscriber_state: 'not-a-valid-state'
        );
    }

    /**
     * Test that create_subscriber() returns the expected data
     * when an invalid custom field is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscriberWithInvalidCustomFields()
    {

        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress,
            fields: [
                'not_a_custom_field' => 'value'
            ]
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $result->subscriber->id;

        // Assert subscriber exists with correct data.
        $this->assertEquals($result->subscriber->email_address, $emailAddress);
    }

    /**
     * Test that create_subscribers() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscribers()
    {
        $subscribers = [
            [
                'email_address' => str_replace('@convertkit.com', '-1@convertkit.com', $this->generateEmailAddress()),
            ],
            [
                'email_address' => str_replace('@convertkit.com', '-2@convertkit.com', $this->generateEmailAddress()),
            ],
        ];
        $result = $this->api->create_subscribers($subscribers);

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        foreach ($result->subscribers as $i => $subscriber) {
            $this->subscriber_ids[] = $subscriber->id;
        }

        // Assert no failures.
        $this->assertCount(0, $result->failures);

        // Assert subscribers exists with correct data.
        foreach ($result->subscribers as $i => $subscriber) {
            $this->assertEquals($subscriber->email_address, $subscribers[$i]['email_address']);
        }
    }

    /**
     * Test that create_subscribers() throws a ClientException when no data is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscribersWithBlankData()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->create_subscribers([
            [],
        ]);
    }

    /**
     * Test that create_subscribers() returns the expected data when invalid email addresses
     * are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreateSubscribersWithInvalidEmailAddresses()
    {
        $subscribers = [
            [
                'email_address' => 'not-an-email-address',
            ],
            [
                'email_address' => 'not-an-email-address-again',
            ],
        ];
        $result = $this->api->create_subscribers($subscribers);

        // Assert no subscribers were added.
        $this->assertCount(0, $result->subscribers);
        $this->assertCount(2, $result->failures);
    }

    /**
     * Test that get_subscriber_id() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSubscriberID()
    {
        $subscriber_id = $this->api->get_subscriber_id($_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
        $this->assertIsInt($subscriber_id);
        $this->assertEquals($subscriber_id, (int) $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);
    }

    /**
     * Test that get_subscriber_id() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSubscriberIDWithInvalidEmailAddress()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_subscriber_id('not-an-email-address');
    }

    /**
     * Test that get_subscriber_id() return false when no subscriber found
     * matching the given email address.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSubscriberIDWithNotSubscribedEmailAddress()
    {
        $result = $this->api->get_subscriber_id('not-a-subscriber@test.com');
        $this->assertFalse($result);
    }

    /**
     * Test that get_subscriber() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSubscriber()
    {
        $result = $this->api->get_subscriber((int) $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);

        // Assert subscriber exists with correct data.
        $this->assertEquals($result->subscriber->id, $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);
        $this->assertEquals($result->subscriber->email_address, $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
    }

    /**
     * Test that get_subscriber() throws a ClientException when an invalid
     * subscriber ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSubscriberWithInvalidSubscriberID()
    {
        $this->expectException(ClientException::class);
        $subscriber = $this->api->get_subscriber(12345);
    }

    /**
     * Test that update_subscriber() works when no changes are made.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUpdateSubscriberWithNoChanges()
    {
        $result = $this->api->update_subscriber($_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);

        // Assert subscriber exists with correct data.
        $this->assertEquals($result->subscriber->id, $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);
        $this->assertEquals($result->subscriber->email_address, $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']);
    }

    /**
     * Test that update_subscriber() works when updating the subscriber's first name.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUpdateSubscriberFirstName()
    {
        // Add a subscriber.
        $firstName = 'FirstName';
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $result->subscriber->id;

        // Assert subscriber created with no first name.
        $this->assertNull($result->subscriber->first_name);

        // Get subscriber ID.
        $subscriberID = $result->subscriber->id;

        // Update subscriber's first name.
        $result = $this->api->update_subscriber(
            subscriber_id: $subscriberID,
            first_name: $firstName
        );

        // Assert changes were made.
        $this->assertEquals($result->subscriber->id, $subscriberID);
        $this->assertEquals($result->subscriber->first_name, $firstName);
    }

    /**
     * Test that update_subscriber() works when updating the subscriber's email address.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUpdateSubscriberEmailAddress()
    {
        // Add a subscriber.
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $result->subscriber->id;

        // Assert subscriber created.
        $this->assertEquals($result->subscriber->email_address, $emailAddress);

        // Get subscriber ID.
        $subscriberID = $result->subscriber->id;

        // Update subscriber's email address.
        $newEmail = $this->generateEmailAddress();
        $result = $this->api->update_subscriber(
            subscriber_id: $subscriberID,
            email_address: $newEmail
        );

        // Assert changes were made.
        $this->assertEquals($result->subscriber->id, $subscriberID);
        $this->assertEquals($result->subscriber->email_address, $newEmail);
    }

    /**
     * Test that update_subscriber() works when updating the subscriber's custom fields.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUpdateSubscriberCustomFields()
    {
        // Add a subscriber.
        $lastName = 'LastName';
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $result->subscriber->id;

        // Assert subscriber created.
        $this->assertEquals($result->subscriber->email_address, $emailAddress);

        // Get subscriber ID.
        $subscriberID = $result->subscriber->id;

        // Update subscriber's custom fields.
        $result = $this->api->update_subscriber(
            subscriber_id: $subscriberID,
            fields: [
                'last_name' => $lastName,
            ]
        );

        // Assert changes were made.
        $this->assertEquals($result->subscriber->id, $subscriberID);
        $this->assertEquals($result->subscriber->fields->last_name, $lastName);
    }

    /**
     * Test that update_subscriber() throws a ClientException when an invalid
     * subscriber ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUpdateSubscriberWithInvalidSubscriberID()
    {
        $this->expectException(ClientException::class);
        $subscriber = $this->api->update_subscriber(12345);
    }

    /**
     * Test that unsubscribe_by_email() works with a valid subscriber email address.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUnsubscribeByEmail()
    {
        // Add a subscriber.
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Unsubscribe.
        $this->assertNull($this->api->unsubscribe_by_email($emailAddress));
    }

    /**
     * Test that unsubscribe_by_email() throws a ClientException when an email
     * address is specified that is not subscribed.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUnsubscribeByEmailWithNotSubscribedEmailAddress()
    {
        $this->expectException(ClientException::class);
        $subscriber = $this->api->unsubscribe_by_email('not-subscribed@convertkit.com');
    }

    /**
     * Test that unsubscribe_by_email() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUnsubscribeByEmailWithInvalidEmailAddress()
    {
        $this->expectException(ClientException::class);
        $subscriber = $this->api->unsubscribe_by_email('invalid-email');
    }

    /**
     * Test that unsubscribe() works with a valid subscriber ID.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testUnsubscribe()
    {
        // Add a subscriber.
        $emailAddress = $this->generateEmailAddress();
        $result = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Unsubscribe.
        $this->assertNull($this->api->unsubscribe($result->subscriber->id));
    }

    /**
     * Test that unsubscribe() throws a ClientException when an invalid
     * subscriber ID is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testUnsubscribeWithInvalidSubscriberID()
    {
        $this->expectException(ClientException::class);
        $subscriber = $this->api->unsubscribe(12345);
    }

    /**
     * Test that get_subscriber_tags() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSubscriberTags()
    {
        $result = $this->api->get_subscriber_tags((int) $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']);

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_subscriber_tags() returns the expected data
     * when the total count is included.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscriberTagsWithTotalCount()
    {
        $result = $this->api->get_subscriber_tags(
            subscriber_id: (int) $_ENV['CONVERTKIT_API_SUBSCRIBER_ID'],
            include_total_count: true
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_subscriber_tags() throws a ClientException when an invalid
     * subscriber ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSubscriberTagsWithInvalidSubscriberID()
    {
        $this->expectException(ClientException::class);
        $subscriber = $this->api->get_subscriber_tags(12345);
    }

    /**
     * Test that get_subscriber_tags() returns the expected data
     * when a valid Subscriber ID is specified and pagination parameters
     * and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSubscriberTagsPagination()
    {
        $result = $this->api->get_subscriber_tags(
            subscriber_id: (int) $_ENV['CONVERTKIT_API_SUBSCRIBER_ID'],
            per_page: 1
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Assert a single tag was returned.
        $this->assertCount(1, $result->tags);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_subscriber_tags(
            subscriber_id: (int) $_ENV['CONVERTKIT_API_SUBSCRIBER_ID'],
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Assert a single tag was returned.
        $this->assertCount(1, $result->tags);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_subscriber_tags(
            subscriber_id: (int) $_ENV['CONVERTKIT_API_SUBSCRIBER_ID'],
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert tags and pagination exist.
        $this->assertDataExists($result, 'tags');
        $this->assertPaginationExists($result);

        // Assert a single tag was returned.
        $this->assertCount(1, $result->tags);
    }

    /**
     * Test that get_email_templates() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetEmailTemplates()
    {
        $result = $this->api->get_email_templates();

        // Assert email templates and pagination exist.
        $this->assertDataExists($result, 'email_templates');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_email_templates() returns the expected data
     * when the total count is included.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetEmailTemplatesWithTotalCount()
    {
        $result = $this->api->get_email_templates(
            include_total_count: true
        );

        // Assert email templates and pagination exist.
        $this->assertDataExists($result, 'email_templates');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_email_templates() returns the expected data
     * when pagination parameters and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetEmailTemplatesPagination()
    {
        $result = $this->api->get_email_templates(
            per_page: 1
        );

        // Assert email templates and pagination exist.
        $this->assertDataExists($result, 'email_templates');
        $this->assertPaginationExists($result);

        // Assert a single email template was returned.
        $this->assertCount(1, $result->email_templates);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_email_templates(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert email templates and pagination exist.
        $this->assertDataExists($result, 'email_templates');
        $this->assertPaginationExists($result);

        // Assert a single email template was returned.
        $this->assertCount(1, $result->email_templates);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_email_templates(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert email templates and pagination exist.
        $this->assertDataExists($result, 'email_templates');
        $this->assertPaginationExists($result);

        // Assert a single email template was returned.
        $this->assertCount(1, $result->email_templates);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);
    }


    /**
     * Test that get_broadcasts() returns the expected data
     * when pagination parameters and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetBroadcastsPagination()
    {
        $result = $this->api->get_broadcasts(
            per_page: 1
        );

        // Assert broadcasts and pagination exist.
        $this->assertDataExists($result, 'broadcasts');
        $this->assertPaginationExists($result);

        // Assert a single broadcast was returned.
        $this->assertCount(1, $result->broadcasts);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_broadcasts(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert broadcasts and pagination exist.
        $this->assertDataExists($result, 'broadcasts');
        $this->assertPaginationExists($result);

        // Assert a single broadcast was returned.
        $this->assertCount(1, $result->broadcasts);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_broadcasts(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert broadcasts and pagination exist.
        $this->assertDataExists($result, 'broadcasts');
        $this->assertPaginationExists($result);

        // Assert a single broadcast was returned.
        $this->assertCount(1, $result->broadcasts);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);
    }

    /**
     * Test that create_broadcast(), update_broadcast() and delete_broadcast() works
     * when specifying valid published_at and send_at values.
     *
     * We do all tests in a single function, so we don't end up with unnecessary Broadcasts remaining
     * on the ConvertKit account when running tests, which might impact
     * other tests that expect (or do not expect) specific Broadcasts.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateAndUpdateDraftBroadcast()
    {
        // Create a broadcast first.
        $result = $this->api->create_broadcast(
            subject: 'Test Subject',
            content: 'Test Content',
            description: 'Test Broadcast from PHP SDK',
        );
        $broadcastID = $result->broadcast->id;

        // Confirm the Broadcast saved.
        $result = get_object_vars($result->broadcast);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Test Subject', $result['subject']);
        $this->assertEquals('Test Content', $result['content']);
        $this->assertEquals('Test Broadcast from PHP SDK', $result['description']);
        $this->assertEquals(null, $result['published_at']);
        $this->assertEquals(null, $result['send_at']);

        // Update the existing broadcast.
        $result = $this->api->update_broadcast(
            id: $broadcastID,
            subject: 'New Test Subject',
            content: 'New Test Content',
            description: 'New Test Broadcast from PHP SDK'
        );

        // Confirm the changes saved.
        $result = get_object_vars($result->broadcast);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('New Test Subject', $result['subject']);
        $this->assertEquals('New Test Content', $result['content']);
        $this->assertEquals('New Test Broadcast from PHP SDK', $result['description']);
        $this->assertEquals(null, $result['published_at']);
        $this->assertEquals(null, $result['send_at']);

        // Delete Broadcast.
        $this->api->delete_broadcast($broadcastID);
        $this->assertEquals(204, $this->api->getResponseInterface()->getStatusCode());
    }

    /**
     * Test that create_broadcast() works when specifying valid published_at and send_at values.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreatePublicBroadcastWithValidDates()
    {
        // Create DateTime object.
        $publishedAt = new DateTime('now');
        $publishedAt->modify('+7 days');
        $sendAt = new DateTime('now');
        $sendAt->modify('+14 days');

        // Create broadcast first.
        $result = $this->api->create_broadcast(
            subject: 'Test Subject',
            content: 'Test Content',
            description: 'Test Broadcast from PHP SDK',
            public: true,
            published_at: $publishedAt,
            send_at: $sendAt
        );
        $broadcastID = $result->broadcast->id;

        // Set broadcast_id to ensure broadcast is deleted after test.
        $this->broadcast_ids[] = $broadcastID;

        // Confirm the Broadcast saved.
        $result = get_object_vars($result->broadcast);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Test Subject', $result['subject']);
        $this->assertEquals('Test Content', $result['content']);
        $this->assertEquals('Test Broadcast from PHP SDK', $result['description']);
        $this->assertEquals(
            $publishedAt->format('Y-m-d') . 'T' . $publishedAt->format('H:i:s') . 'Z',
            $result['published_at']
        );
        $this->assertEquals(
            $sendAt->format('Y-m-d') . 'T' . $sendAt->format('H:i:s') . 'Z',
            $result['send_at']
        );
    }

    /**
     * Test that get_broadcast() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetBroadcast()
    {
        $result = $this->api->get_broadcast($_ENV['CONVERTKIT_API_BROADCAST_ID']);
        $result = get_object_vars($result->broadcast);
        $this->assertEquals($result['id'], $_ENV['CONVERTKIT_API_BROADCAST_ID']);
    }

    /**
     * Test that get_broadcast() throws a ClientException when an invalid
     * broadcast ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetBroadcastWithInvalidBroadcastID()
    {
        $this->expectException(ClientException::class);
        $this->api->get_broadcast(12345);
    }

    /**
     * Test that get_broadcast_stats() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetBroadcastStats()
    {
        $result = $this->api->get_broadcast_stats($_ENV['CONVERTKIT_API_BROADCAST_ID']);
        $result = get_object_vars($result->broadcast);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('stats', $result);
        $this->assertEquals($result['stats']->recipients, 1);
        $this->assertEquals($result['stats']->open_rate, 0);
        $this->assertEquals($result['stats']->click_rate, 0);
        $this->assertEquals($result['stats']->unsubscribes, 0);
        $this->assertEquals($result['stats']->total_clicks, 0);
    }

    /**
     * Test that get_broadcast_stats() throws a ClientException when an invalid
     * broadcast ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetBroadcastStatsWithInvalidBroadcastID()
    {
        $this->expectException(ClientException::class);
        $this->api->get_broadcast_stats(12345);
    }

    /**
     * Test that update_broadcast() throws a ClientException when an invalid
     * broadcast ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUpdateBroadcastWithInvalidBroadcastID()
    {
        $this->expectException(ClientException::class);
        $this->api->update_broadcast(12345);
    }

    /**
     * Test that delete_broadcast() throws a ClientException when an invalid
     * broadcast ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testDeleteBroadcastWithInvalidBroadcastID()
    {
        $this->expectException(ClientException::class);
        $this->api->delete_broadcast(12345);
    }

    /**
     * Test that get_webhooks() returns the expected data
     * when pagination parameters and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetWebhooksPagination()
    {
        // Create webhooks first.
        $results = [
            $this->api->create_webhook(
                url: 'https://webhook.site/' . str_shuffle('wfervdrtgsdewrafvwefds'),
                event: 'subscriber.subscriber_activate',
            ),
            $this->api->create_webhook(
                url: 'https://webhook.site/' . str_shuffle('wfervdrtgsdewrafvwefds'),
                event: 'subscriber.subscriber_activate',
            ),
        ];

        // Set webhook_ids to ensure webhooks are deleted after test.
        $this->webhook_ids = [
            $results[0]->webhook->id,
            $results[1]->webhook->id,
        ];

        // Get webhooks.
        $result = $this->api->get_webhooks(
            per_page: 1
        );

        // Assert webhooks and pagination exist.
        $this->assertDataExists($result, 'webhooks');
        $this->assertPaginationExists($result);

        // Assert a single webhook was returned.
        $this->assertCount(1, $result->webhooks);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_webhooks(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert webhooks and pagination exist.
        $this->assertDataExists($result, 'webhooks');
        $this->assertPaginationExists($result);

        // Assert a single webhook was returned.
        $this->assertCount(1, $result->webhooks);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertFalse($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_webhooks(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert webhooks and pagination exist.
        $this->assertDataExists($result, 'webhooks');
        $this->assertPaginationExists($result);

        // Assert a single webhook was returned.
        $this->assertCount(1, $result->webhooks);
    }

    /**
     * Test that create_webhook(), get_webhooks() and delete_webhook() works.
     *
     * We do both, so we don't end up with unnecessary webhooks remaining
     * on the ConvertKit account when running tests.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateGetAndDeleteWebhook()
    {
        // Create a webhook first.
        $result = $this->api->create_webhook(
            url: 'https://webhook.site/' . str_shuffle('wfervdrtgsdewrafvwefds'),
            event: 'subscriber.subscriber_activate',
        );
        $id = $result->webhook->id;

        // Get webhooks.
        $result = $this->api->get_webhooks();

        // Assert webhooks and pagination exist.
        $this->assertDataExists($result, 'webhooks');
        $this->assertPaginationExists($result);

        // Get webhooks including total count.
        $result = $this->api->get_webhooks(
            include_total_count: true
        );

        // Assert webhooks and pagination exist.
        $this->assertDataExists($result, 'webhooks');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);

        // Delete the webhook.
        $result = $this->api->delete_webhook($id);
    }

    /**
     * Test that create_webhook() works with an event parameter.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateWebhookWithEventParameter()
    {
        // Create a webhook.
        $url = 'https://webhook.site/' . str_shuffle('wfervdrtgsdewrafvwefds');
        $result = $this->api->create_webhook(
            url: $url,
            event: 'subscriber.form_subscribe',
            parameter: $_ENV['CONVERTKIT_API_FORM_ID']
        );

        // Confirm webhook created with correct data.
        $this->assertArrayHasKey('webhook', get_object_vars($result));
        $this->assertArrayHasKey('id', get_object_vars($result->webhook));
        $this->assertArrayHasKey('target_url', get_object_vars($result->webhook));
        $this->assertEquals($result->webhook->target_url, $url);
        $this->assertEquals($result->webhook->event->name, 'form_subscribe');
        $this->assertEquals($result->webhook->event->form_id, $_ENV['CONVERTKIT_API_FORM_ID']);

        // Delete the webhook.
        $result = $this->api->delete_webhook($result->webhook->id);
    }

    /**
     * Test that create_webhook() throws an InvalidArgumentException when an invalid
     * event is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateWebhookWithInvalidEvent()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api->create_webhook(
            url: 'https://webhook.site/' . str_shuffle('wfervdrtgsdewrafvwefds'),
            event: 'invalid.event'
        );
    }

    /**
     * Test that delete_webhook() throws a ClientException when an invalid
     * ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testDeleteWebhookWithInvalidID()
    {
        $this->expectException(ClientException::class);
        $this->api->delete_webhook(12345);
    }

    /**
     * Test that get_custom_fields() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetCustomFields()
    {
        $result = $this->api->get_custom_fields();

        // Assert custom fields and pagination exist.
        $this->assertDataExists($result, 'custom_fields');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_custom_fields() returns the expected data
     * when the total count is included.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetCustomFieldsWithTotalCount()
    {
        $result = $this->api->get_custom_fields(
            include_total_count: true
        );

        // Assert custom fields and pagination exist.
        $this->assertDataExists($result, 'custom_fields');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_custom_fields() returns the expected data
     * when pagination parameters and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetCustomFieldsPagination()
    {
        $result = $this->api->get_custom_fields(
            per_page: 1
        );

        // Assert custom fields and pagination exist.
        $this->assertDataExists($result, 'custom_fields');
        $this->assertPaginationExists($result);

        // Assert a single custom field was returned.
        $this->assertCount(1, $result->custom_fields);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_custom_fields(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert custom fields and pagination exist.
        $this->assertDataExists($result, 'custom_fields');
        $this->assertPaginationExists($result);

        // Assert a single custom field was returned.
        $this->assertCount(1, $result->custom_fields);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_custom_fields(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert custom fields and pagination exist.
        $this->assertDataExists($result, 'custom_fields');
        $this->assertPaginationExists($result);

        // Assert a single custom field was returned.
        $this->assertCount(1, $result->custom_fields);
    }

    /**
     * Test that create_custom_field() works.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateCustomField()
    {
        $label = 'Custom Field ' . mt_rand();
        $result = $this->api->create_custom_field($label);

        // Set custom_field_ids to ensure custom fields are deleted after test.
        $this->custom_field_ids[] = $result->custom_field->id;

        $result = get_object_vars($result->custom_field);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('key', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals($result['label'], $label);
    }

    /**
     * Test that create_custom_field() throws a ClientException when a blank
     * label is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateCustomFieldWithBlankLabel()
    {
        $this->expectException(ClientException::class);
        $this->api->create_custom_field('');
    }

    /**
     * Test that create_custom_fields() works.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreateCustomFields()
    {
        $labels = [
            'Custom Field ' . mt_rand(),
            'Custom Field ' . mt_rand(),
        ];
        $result = $this->api->create_custom_fields($labels);

        // Set custom_field_ids to ensure custom fields are deleted after test.
        foreach ($result->custom_fields as $index => $customField) {
            $this->custom_field_ids[] = $customField->id;
        }

        // Assert no failures.
        $this->assertCount(0, $result->failures);

        // Confirm result is an array comprising of each custom field that was created.
        $this->assertIsArray($result->custom_fields);
    }

    /**
     * Test that update_custom_field() works.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUpdateCustomField()
    {
        // Create custom field.
        $label = 'Custom Field ' . mt_rand();
        $result = $this->api->create_custom_field($label);
        $id = $result->custom_field->id;

        // Set custom_field_ids to ensure custom fields are deleted after test.
        $this->custom_field_ids[] = $result->custom_field->id;

        // Change label.
        $newLabel = 'Custom Field ' . mt_rand();
        $this->api->update_custom_field($id, $newLabel);

        // Confirm label changed.
        $customFields = $this->api->get_custom_fields();
        foreach ($customFields->custom_fields as $customField) {
            if ($customField->id === $id) {
                $this->assertEquals($customField->label, $newLabel);
            }
        }
    }

    /**
     * Test that update_custom_field() throws a ClientException when an
     * invalid custom field ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testUpdateCustomFieldWithInvalidID()
    {
        $this->expectException(ClientException::class);
        $this->api->update_custom_field(12345, 'Something');
    }

    /**
     * Test that delete_custom_field() works.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testDeleteCustomField()
    {
        // Create custom field.
        $label = 'Custom Field ' . mt_rand();
        $result = $this->api->create_custom_field($label);
        $id = $result->custom_field->id;

        // Delete custom field as tests passed.
        $this->api->delete_custom_field($id);

        // Confirm custom field no longer exists.
        $customFields = $this->api->get_custom_fields();
        foreach ($customFields->custom_fields as $customField) {
            $this->assertNotEquals($customField->id, $id);
        }
    }

    /**
     * Test that delete_custom_field() throws a ClientException when an
     * invalid custom field ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testDeleteCustomFieldWithInvalidID()
    {
        $this->expectException(ClientException::class);
        $this->api->delete_custom_field(12345);
    }

    /**
     * Test that get_purchases() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetPurchases()
    {
        $result = $this->api->get_purchases();

        // Assert purchases and pagination exist.
        $this->assertDataExists($result, 'purchases');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_purchases() returns the expected data
     * when the total count is included.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetPurchasesWithTotalCount()
    {
        $result = $this->api->get_purchases(
            include_total_count: true
        );

        // Assert purchases and pagination exist.
        $this->assertDataExists($result, 'purchases');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_purchases() returns the expected data
     * when pagination parameters and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetPurchasesPagination()
    {
        $result = $this->api->get_purchases(
            per_page: 1
        );

        // Assert purchases and pagination exist.
        $this->assertDataExists($result, 'purchases');
        $this->assertPaginationExists($result);

        // Assert a single purchase was returned.
        $this->assertCount(1, $result->purchases);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_purchases(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert purchases and pagination exist.
        $this->assertDataExists($result, 'purchases');
        $this->assertPaginationExists($result);

        // Assert a single purchase was returned.
        $this->assertCount(1, $result->purchases);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_purchases(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert purchases and pagination exist.
        $this->assertDataExists($result, 'purchases');
        $this->assertPaginationExists($result);

        // Assert a single purchase was returned.
        $this->assertCount(1, $result->purchases);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);
    }

    /**
     * Test that get_purchases() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetPurchase()
    {
        // Get ID of first purchase.
        $purchases = $this->api->get_purchases(
            per_page: 1
        );
        $id = $purchases->purchases[0]->id;

        // Get purchase.
        $result = $this->api->get_purchase($id);
        $this->assertInstanceOf('stdClass', $result);
        $this->assertEquals($purchases->purchases[0]->id, $id);
    }

    /**
     * Test that get_purchases() throws a ClientException when an invalid
     * purchase ID is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetPurchaseWithInvalidID()
    {
        $this->expectException(ClientException::class);
        $this->api->get_purchase(12345);
    }

    /**
     * Test that create_purchase() returns the expected data.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testCreatePurchase()
    {
        $purchase = $this->api->create_purchase(
            // Required fields.
            email_address: $this->generateEmailAddress(),
            transaction_id: str_shuffle('wfervdrtgsdewrafvwefds'),
            currency: 'usd',
            products: [
                [
                    'name' => 'Floppy Disk (512k)',
                    'sku' => '7890-ijkl',
                    'pid' => 9999,
                    'lid' => 7777,
                    'quantity' => 2,
                    'unit_price' => 5.00,
                ],
                [
                    'name' => 'Telephone Cord (data)',
                    'sku' => 'mnop-1234',
                    'pid' => 5555,
                    'lid' => 7778,
                    'quantity' => 1,
                    'unit_price' => 10.00,
                ],
            ],
            // Optional fields.
            first_name: 'Tim',
            status: 'paid',
            subtotal: 20.00,
            tax: 2.00,
            shipping: 2.00,
            discount: 3.00,
            total: 21.00,
            transaction_time: new DateTime('now'),
        );

        $this->assertInstanceOf('stdClass', $purchase);
        $this->assertArrayHasKey('transaction_id', get_object_vars($purchase->purchase));
    }

    /**
     * Test that create_purchase() throws a ClientException when an invalid
     * email address is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreatePurchaseWithInvalidEmailAddress()
    {
        $this->expectException(ClientException::class);
        $this->api->create_purchase(
            email_address: 'not-an-email-address',
            transaction_id: str_shuffle('wfervdrtgsdewrafvwefds'),
            currency: 'usd',
            products: [
                [
                    'name' => 'Floppy Disk (512k)',
                    'sku' => '7890-ijkl',
                    'pid' => 9999,
                    'lid' => 7777,
                    'quantity' => 2,
                    'unit_price' => 5.00,
                ],
            ],
        );
    }

    /**
     * Test that create_purchase() throws a ClientException when a blank
     * transaction ID is specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreatePurchaseWithBlankTransactionID()
    {
        $this->expectException(ClientException::class);
        $this->api->create_purchase(
            email_address: $this->generateEmailAddress(),
            transaction_id: '',
            currency: 'usd',
            products: [
                [
                    'name' => 'Floppy Disk (512k)',
                    'sku' => '7890-ijkl',
                    'pid' => 9999,
                    'lid' => 7777,
                    'quantity' => 2,
                    'unit_price' => 5.00,
                ],
            ],
        );
    }

    /**
     * Test that create_purchase() throws a ClientException when no products
     * are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testCreatePurchaseWithNoProducts()
    {
        $this->expectException(ClientException::class);
        $this->api->create_purchase(
            email_address: $this->generateEmailAddress(),
            transaction_id: str_shuffle('wfervdrtgsdewrafvwefds'),
            currency: 'usd',
            products: [],
        );
    }

    /**
     * Test that get_segments() returns the expected data.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSegments()
    {
        $result = $this->api->get_segments();

        // Assert segments and pagination exist.
        $this->assertDataExists($result, 'segments');
        $this->assertPaginationExists($result);
    }

    /**
     * Test that get_segments() returns the expected data
     * when the total count is included.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetSegmentsWithTotalCount()
    {
        $result = $this->api->get_segments(
            include_total_count: true
        );

        // Assert segments and pagination exist.
        $this->assertDataExists($result, 'segments');
        $this->assertPaginationExists($result);

        // Assert total count is included.
        $this->assertArrayHasKey('total_count', get_object_vars($result->pagination));
        $this->assertGreaterThan(0, $result->pagination->total_count);
    }

    /**
     * Test that get_segments() returns the expected data
     * when pagination parameters and per_page limits are specified.
     *
     * @since   2.0.0
     *
     * @return void
     */
    public function testGetSegmentsPagination()
    {
        $result = $this->api->get_segments(
            per_page: 1
        );

        // Assert segments and pagination exist.
        $this->assertDataExists($result, 'segments');
        $this->assertPaginationExists($result);

        // Assert a single segment was returned.
        $this->assertCount(1, $result->segments);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch next page.
        $result = $this->api->get_segments(
            per_page: 1,
            after_cursor: $result->pagination->end_cursor
        );

        // Assert segments and pagination exist.
        $this->assertDataExists($result, 'segments');
        $this->assertPaginationExists($result);

        // Assert a single segment was returned.
        $this->assertCount(1, $result->segments);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertTrue($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);

        // Use pagination to fetch previous page.
        $result = $this->api->get_segments(
            per_page: 1,
            before_cursor: $result->pagination->start_cursor
        );

        // Assert segments and pagination exist.
        $this->assertDataExists($result, 'segments');
        $this->assertPaginationExists($result);

        // Assert a single segment was returned.
        $this->assertCount(1, $result->segments);

        // Assert has_previous_page and has_next_page are correct.
        $this->assertFalse($result->pagination->has_previous_page);
        $this->assertTrue($result->pagination->has_next_page);
    }

    /**
     * Test that fetching a legacy form's markup works.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetResourceLegacyForm()
    {
        $markup = $this->api->get_resource($_ENV['CONVERTKIT_API_LEGACY_FORM_URL']);

        // Assert that the markup is HTML.
        $this->assertTrue($this->isHtml($markup));

        // Confirm that encoding works correctly.
        $this->assertStringContainsString('Vantar þinn ungling sjálfstraust í stærðfræði?', $markup);
    }

    /**
     * Test that fetching a landing page's markup works.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetResourceLandingPage()
    {
        $markup = $this->api->get_resource($_ENV['CONVERTKIT_API_LANDING_PAGE_URL']);

        // Assert that the markup is HTML.
        $this->assertTrue($this->isHtml($markup));

        // Confirm that encoding works correctly.
        $this->assertStringContainsString('Vantar þinn ungling sjálfstraust í stærðfræði?', $markup);
    }

    /**
     * Test that fetching a legacy landing page's markup works.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetResourceLegacyLandingPage()
    {
        $markup = $this->api->get_resource($_ENV['CONVERTKIT_API_LEGACY_LANDING_PAGE_URL']);

        // Assert that the markup is HTML.
        $this->assertTrue($this->isHtml($markup));

        // Confirm that encoding works correctly.
        $this->assertStringContainsString('Legacy Landing Page', $markup);
    }

    /**
     * Test that get_resource() throws an InvalidArgumentException when an invalid
     * URL is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetResourceInvalidURL()
    {
        $this->expectException(InvalidArgumentException::class);
        $markup = $this->api->get_resource('not-a-url');
    }

    /**
     * Test that get_resource() throws a ClientException when an inaccessible
     * URL is specified.
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function testGetResourceInaccessibleURL()
    {
        $this->expectException(ClientException::class);
        $markup = $this->api->get_resource('https://convertkit.com/a/url/that/does/not/exist');
    }

    /**
     * Deletes the src/logs/debug.log file, if it remains following a previous test.
     *
     * @since   1.2.0
     *
     * @return  void
     */
    private function deleteLogFile()
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    /**
     * Returns the contents of the src/logs/debug.log file.
     *
     * @since   1.2.0
     *
     * @return  string
     */
    private function getLogFileContents()
    {
        // Return blank string if no log file.
        if (!file_exists($this->logFile)) {
            return '';
        }

        // Return log file contents.
        return file_get_contents($this->logFile);
    }

    /**
     * Helper method to call a class' private method.
     *
     * @since   2.0.0
     *
     * @param   mixed  $obj  Class Object.
     * @param   string $name Method Name.
     * @param   array  $args Method Arguments.
     */
    private function callPrivateMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    /**
     * Generates a unique email address for use in a test, comprising of a prefix,
     * date + time and PHP version number.
     *
     * This ensures that if tests are run in parallel, the same email address
     * isn't used for two tests across parallel testing runs.
     *
     * @since   1.0.0
     *
     * @param   string $domain     Domain (default: kit.com).
     *
     * @return  string
     */
    private function generateEmailAddress($domain = 'kit.com')
    {
        return 'php-sdk-' . date('Y-m-d-H-i-s') . '-php-' . PHP_VERSION_ID . '@' . $domain;
    }

    /**
     * Checks if string is html
     *
     * @since   1.0.0
     *
     * @param   $string Possible HTML.
     * @return  bool
     */
    private function isHtml($string)
    {
        return preg_match("/<[^<]+>/", $string, $m) != 0;
    }

    /**
     * Helper method to mock an API response.
     *
     * @since   2.0.0
     *
     * @param   ConvertKitAPI $api  ConvertKit API Class.
     * @param   null|array    $responseBody     Response to return when API call is made.
     * @param   int           $httpCode         HTTP Code to return when API call is made.
     */
    private function mockResponse(ConvertKit_API $api, $responseBody = null, int $httpCode = 200)
    {
        // Setup API with a mock Guzzle client, returning the data
        // as if we successfully swapped an auth code for an access token.
        $mock = new MockHandler([
            new Response(
                status: $httpCode,
                body: json_encode($responseBody)
            ),
        ]);

        // Define client with mock handler.
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        // Set Client to use for the API.
        $api->set_http_client($client);

        // Return API object.
        return $api;
    }

    /**
     * Helper method to assert the given key exists as an array
     * in the API response.
     *
     * @since   2.0.0
     *
     * @param   array   $result     API Result.
     */
    private function assertDataExists($result, $key)
    {
        $result = get_object_vars($result);
        $this->assertArrayHasKey($key, $result);
        $this->assertIsArray($result[$key]);
    }

    /**
     * Helper method to assert pagination object exists in response.
     *
     * @since   2.0.0
     *
     * @param   array   $result     API Result.
     */
    private function assertPaginationExists($result)
    {
        $result = get_object_vars($result);
        $this->assertArrayHasKey('pagination', $result);
        $pagination = get_object_vars($result['pagination']);
        $this->assertArrayHasKey('has_previous_page', $pagination);
        $this->assertArrayHasKey('has_next_page', $pagination);
        $this->assertArrayHasKey('start_cursor', $pagination);
        $this->assertArrayHasKey('end_cursor', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
    }
}
