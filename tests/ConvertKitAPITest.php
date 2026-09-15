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
 *
 * Extended by ConvertKitAPIKeyTest and ConvertKitAPIOAuthTest, which define
 * the authentication method to use when running these tests.
 */
abstract class ConvertKitAPITest extends TestCase
{
    use TestsTrait;

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
     * Webhook Endpoint IDs to delete on teardown of a test.
     *
     * @since   2.8.0
     *
     * @var     array<int, int>
     */
    protected $webhook_endpoint_ids = [];

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

        // Unsubscribe any Subscribers.
        foreach ($this->subscriber_ids as $id) {
            $this->api->unsubscribe($id);
        }

        // Delete any Webhooks.
        foreach ($this->webhook_ids as $id) {
            $this->api->delete_webhook($id);
        }

        // Delete any Broadcasts.
        foreach ($this->broadcast_ids as $id) {
            $this->api->delete_broadcast($id);
        }

        // Delete any Webhook Endpoints.
        foreach ($this->webhook_endpoint_ids as $id) {
            $this->api->delete_webhook_endpoint($id);
        }
    }

    /**
     * Assert that the given callable throws an exception.
     *
     * Any Throwable is accepted by default, as the API may return a ClientException
     * or a ServerException depending on the error. Where the SDK validates arguments
     * before performing an API request, specify $expected, to assert that the SDK's
     * validation produced the error, and not the API.
     *
     * @since   2.7.0
     *
     * @param   callable    $fn       Callable that should fail.
     * @param   string|null $expected Expected exception class name.
     * @return  void
     */
    protected function assertApiError(callable $fn, string|null $expected = null): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            if (!is_null($expected)) {
                $this->assertInstanceOf($expected, $e);
                return;
            }

            $this->assertTrue(true, 'Callable threw an exception as expected.');
            return;
        }
        $this->fail('Expected callable to throw an exception, but none was thrown.');
    }

    /**
     * Assert that the last API response had the given HTTP status code.
     *
     * @since   2.7.0
     *
     * @param   int $expected Expected HTTP status code.
     * @return  void
     */
    protected function assertLastResponseStatusCode(int $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->api->getResponseInterface()->getStatusCode()
        );
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
     * Test that create_snippet() works.
     *
     * @since   2.5.0
     *
     * @return void
     */
    public function testCreateSnippet()
    {
        // Add mock handler for this API request, as the API doesn't provide
        // a method to delete snippets to cleanup the test.
        $this->api = $this->mockResponse(
            api: $this->api,
            responseBody: [
                'snippet' => [
                    'id' => 12345,
                    'name' => 'Test Snippet',
                    'snippet_type' => 'inline',
                    'content' => 'Test Content',
                ],
            ]
        );

        // Create a snippet.
        $result = $this->api->create_snippet(
            name: 'Test Snippet',
            snippet_type: 'inline',
            content: 'Test Content'
        );
        $snippetID = $result->snippet->id;

        // Confirm the Snippet saved.
        $result = get_object_vars($result->snippet);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Test Snippet', $result['name']);
        $this->assertEquals('inline', $result['snippet_type']);
        $this->assertEquals('Test Content', $result['content']);
    }

    /**
     * Test that create_tag() returns the expected data.
     *
     * Kept here rather than in the trait: no delete-tag endpoint exists,
     * so the test mocks the Guzzle response to avoid polluting the account.
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
     * Deletes the src/logs/debug.log file, if it remains following a previous test.
     *
     * @since   1.2.0
     *
     * @return  void
     */
    public function deleteLogFile()
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
    public function getLogFileContents()
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
    public function callPrivateMethod($obj, $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    /**
     * Helper method to mock an API response using a Guzzle MockHandler.
     *
     * @since   2.0.0
     *
     * @param   ConvertKit_API $api          Kit API class.
     * @param   null|array     $responseBody Response body to return.
     * @param   int            $httpCode     HTTP status code to return.
     */
    public function mockResponse(ConvertKit_API $api, $responseBody = null, int $httpCode = 200)
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
}
