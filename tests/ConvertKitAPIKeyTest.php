<?php

use GuzzleHttp\Exception\ClientException;
use Dotenv\Dotenv;
use ConvertKit_API\ConvertKit_API;

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

    /**
     * Test that create_tags() throws a ClientException when attempting
     * to create tags, as this is only supported using OAuth.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreateTags()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->create_tags([
            'Tag Test ' . mt_rand(),
            'Tag Test ' . mt_rand(),
        ]);
    }

    /**
     * Skip this test from ConvertKitAPITest, as testCreateTags() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreateTagsBlank()
    {
        $this->markTestSkipped('testCreateTags() above confirms a ClientException is thrown.');
    }

    /**
     * Skip this test from ConvertKitAPITest, as testCreateTags() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreateTagsThatExist()
    {
        $this->markTestSkipped('testCreateTags() above confirms a ClientException is thrown.');
    }

    /**
     * Test that tag_subscribers() throws a ClientException when attempting
     * to tag subscribers, as this is only supported using OAuth.
     *
     * @since   2.2.1
     *
     * @return void
     */
    public function testTagSubscribers()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->tag_subscribers(
            [
                [
                    'tag_id' => (int) $_ENV['CONVERTKIT_API_TAG_ID'],
                    'subscriber_id' => (int) $_ENV['CONVERTKIT_API_SUBSCRIBER_ID']
                ],
            ]
        );
    }

    /**
     * Skip this test from ConvertKitAPITest, as testTagSubscribers() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.1
     *
     * @return void
     */
    public function testTagSubscribersWithInvalidTagID()
    {
        $this->markTestSkipped('testTagSubscribers() above confirms a ClientException is thrown.');
    }

    /**
     * Skip this test from ConvertKitAPITest, as testTagSubscribers() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.1
     *
     * @return void
     */
    public function testTagSubscribersWithInvalidSubscriberID()
    {
        $this->markTestSkipped('testTagSubscribers() above confirms a ClientException is thrown.');
    }

    /**
     * Test that add_subscribers_to_forms() throws a ClientException when
     * attempting to add subscribers to forms, as this is only supported
     * using OAuth.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testAddSubscribersToForms()
    {
        // Create subscriber.
        $emailAddress = $this->generateEmailAddress();
        $subscriber = $this->api->create_subscriber(
            email_address: $emailAddress
        );

        // Set subscriber_id to ensure subscriber is unsubscribed after test.
        $this->subscriber_ids[] = $subscriber->subscriber->id;

        $this->expectException(ClientException::class);

        // Add subscribers to forms.
        $result = $this->api->add_subscribers_to_forms(
            forms_subscribers_ids: [
                [
                    'form_id' => (int) $_ENV['CONVERTKIT_API_FORM_ID'],
                    'subscriber_id' => $subscriber->subscriber->id,
                ],
                [
                    'form_id' => (int) $_ENV['CONVERTKIT_API_FORM_ID_2'],
                    'subscriber_id' => $subscriber->subscriber->id,
                ],
            ]
        );
    }

    /**
     * Skip this test from ConvertKitAPITest, as testAddSubscribersToForms() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testAddSubscribersToFormsWithReferrer()
    {
        $this->markTestSkipped('testAddSubscribersToForms() above confirms a ClientException is thrown.');
    }

    /**
     * Skip this test from ConvertKitAPITest, as testAddSubscribersToForms() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testAddSubscribersToFormsWithReferrerUTMParams()
    {
        $this->markTestSkipped('testAddSubscribersToForms() above confirms a ClientException is thrown.');
    }

    /**
     * Skip this test from ConvertKitAPITest, as testAddSubscribersToForms() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testAddSubscribersToFormsWithInvalidFormIDs()
    {
        $this->markTestSkipped('testAddSubscribersToForms() above confirms a ClientException is thrown.');
    }

    /**
     * Skip this test from ConvertKitAPITest, as testAddSubscribersToForms() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testAddSubscribersToFormsWithInvalidSubscriberIDs()
    {
        $this->markTestSkipped('testAddSubscribersToForms() above confirms a ClientException is thrown.');
    }

    /**
     * Test that create_subscribers() returns a ClientException
     * when attempting to create subscribers, as this is only supported
     * using OAuth.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreateSubscribers()
    {
        $this->expectException(ClientException::class);
        $subscribers = [
            [
                'email_address' => str_replace('@kit.com', '-1@kit.com', $this->generateEmailAddress()),
            ],
            [
                'email_address' => str_replace('@kit.com', '-2@kit.com', $this->generateEmailAddress()),
            ],
        ];
        $result = $this->api->create_subscribers($subscribers);
    }

    /**
     * Skip this test from ConvertKitAPITest, as testCreateSubscribersWithBlankData() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreateSubscribersWithBlankData()
    {
        $this->markTestSkipped('testCreateSubscribers() above confirms a ClientException is thrown.');
    }

    /**
     * Skip this test from ConvertKitAPITest, as testCreateSubscribersWithBlankData() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreateSubscribersWithInvalidEmailAddresses()
    {
        $this->markTestSkipped('testCreateSubscribers() above confirms a ClientException is thrown.');
    }

    /**
     * Test that create_custom_fields() throws a ClientException
     * as this is only supported using OAuth.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreateCustomFields()
    {
        $this->expectException(ClientException::class);
        $labels = [
            'Custom Field ' . mt_rand(),
            'Custom Field ' . mt_rand(),
        ];
        $result = $this->api->create_custom_fields($labels);
    }

    /**
     * Test that get_purchases() throws a ClientException
     * as this is only supported using OAuth.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testGetPurchases()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_purchases();
    }

    /**
     * Skip this test from ConvertKitAPITest, as testGetPurchases() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testGetPurchasesWithTotalCount()
    {
        $this->markTestSkipped('testGetPurchases() above confirms a ClientException is thrown.');
    }

    /**
     * Skip this test from ConvertKitAPITest, as testGetPurchases() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testGetPurchasesPagination()
    {
        $this->markTestSkipped('testGetPurchases() above confirms a ClientException is thrown.');
    }

    /**
     * Test that get_purchases() throws a ClientException
     * when a purchase ID is specified, as this is only
     * supported using OAuth.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testGetPurchase()
    {
        $this->expectException(ClientException::class);
        $result = $this->api->get_purchase(12345);
    }

    /**
     * Skip this test from ConvertKitAPITest, as testGetPurchase() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testGetPurchaseWithInvalidID()
    {
        $this->markTestSkipped('testGetPurchase() above confirms a ClientException is thrown.');
    }

    /**
     * Test that create_purchase() throws a ClientException
     * as this is only supported using OAuth.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreatePurchase()
    {
        $this->expectException(ClientException::class);
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
    }

    /**
     * Skip this test from ConvertKitAPITest, as testCreatePurchase() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreatePurchaseWithInvalidEmailAddress()
    {
        $this->markTestSkipped('testCreatePurchase() above confirms a ClientException is thrown.');
    }

    /**
     * Skip this test from ConvertKitAPITest, as testCreatePurchase() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreatePurchaseWithBlankTransactionID()
    {
        $this->markTestSkipped('testCreatePurchase() above confirms a ClientException is thrown.');
    }

    /**
     * Skip this test from ConvertKitAPITest, as testCreatePurchase() above
     * confirms a ClientException is thrown.
     *
     * @since   2.2.0
     *
     * @return void
     */
    public function testCreatePurchaseWithNoProducts()
    {
        $this->markTestSkipped('testCreatePurchase() above confirms a ClientException is thrown.');
    }
}
