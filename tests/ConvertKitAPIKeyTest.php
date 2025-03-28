<?php

use Dotenv\Dotenv;
use ConvertKit_API\ConvertKit_API;

require_once __DIR__ . '/ConvertKitAPITest.php';

/**
 * ConvertKit API Key class tests.
 */
class ConvertKitAPIKeyTest extends ConvertKitAPITest
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

        // Setup API instances.
        $this->api = new ConvertKit_API(
            apiKey: $_ENV['CONVERTKIT_API_KEY']
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
            apiKey: $_ENV['CONVERTKIT_API_KEY'],
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
            apiKey: $_ENV['CONVERTKIT_API_KEY'],
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
            apiKey: $_ENV['CONVERTKIT_API_KEY'],
            debug: true
        );

        // Create log entries with API Key and Email Address, as if an API method
        // were to log this sensitive data.
        $this->callPrivateMethod($api, 'create_log', ['API Key: ' . $_ENV['CONVERTKIT_API_KEY']]);
        $this->callPrivateMethod($api, 'create_log', ['Email: ' . $_ENV['CONVERTKIT_API_SUBSCRIBER_EMAIL']]);

        // Confirm that the log includes the masked API Key and Email Address.
        $this->assertStringContainsString(
            str_repeat(
                '*',
                (strlen($_ENV['CONVERTKIT_API_KEY']) - 4)
            ) . substr($_ENV['CONVERTKIT_API_KEY'], -4),
            $this->getLogFileContents()
        );
        $this->assertStringContainsString(
            'o****@n********.c**',
            $this->getLogFileContents()
        );

        // Confirm that the log does not include the unmasked API Key or Email Address.
        $this->assertStringNotContainsString($_ENV['CONVERTKIT_API_KEY'], $this->getLogFileContents());
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
     * @since   2.2.0
     *
     * @return  void
     */
    public function testRequestHeadersMethod()
    {
        $headers = $this->api->get_request_headers();
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertArrayHasKey('X-Kit-Api-Key', $headers);
        $this->assertEquals($headers['Accept'], 'application/json');
        $this->assertEquals($headers['Content-Type'], 'application/json; charset=utf-8');
        $this->assertEquals($headers['User-Agent'], 'ConvertKitPHPSDK/' . $this->api::VERSION . ';PHP/' . phpversion());
        $this->assertEquals($headers['X-Kit-Api-Key'], $_ENV['CONVERTKIT_API_KEY']);
    }

    /**
     * Test that calling request_headers() with a different `type` parameter
     * returns the expected array of headers
     *
     * @since   2.2.0
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
        $this->assertArrayHasKey('X-Kit-Api-Key', $headers);
        $this->assertEquals($headers['Accept'], 'text/html');
        $this->assertEquals($headers['Content-Type'], 'text/html; charset=utf-8');
        $this->assertEquals($headers['User-Agent'], 'ConvertKitPHPSDK/' . $this->api::VERSION . ';PHP/' . phpversion());
        $this->assertEquals($headers['X-Kit-Api-Key'], $_ENV['CONVERTKIT_API_KEY']);
    }

    /**
     * Test that calling request_headers() with the `auth` parameter set to false
     * returns the expected array of headers
     *
     * @since   2.2.0
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
        $this->assertArrayNotHasKey('X-Kit-Api-Key', $headers);
        $this->assertEquals($headers['Accept'], 'application/json');
        $this->assertEquals($headers['Content-Type'], 'application/json; charset=utf-8');
        $this->assertEquals($headers['User-Agent'], 'ConvertKitPHPSDK/' . $this->api::VERSION . ';PHP/' . phpversion());
    }

    /**
     * Test that calling request_headers() with a different `type` parameter
     * and the `auth` parameter set to false returns the expected array of headers
     *
     * @since   2.2.0
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
        $this->assertArrayNotHasKey('X-Kit-Api-Key', $headers);
        $this->assertEquals($headers['Accept'], 'text/html');
        $this->assertEquals($headers['Content-Type'], 'text/html; charset=utf-8');
        $this->assertEquals($headers['User-Agent'], 'ConvertKitPHPSDK/' . $this->api::VERSION . ';PHP/' . phpversion());
    }
}
