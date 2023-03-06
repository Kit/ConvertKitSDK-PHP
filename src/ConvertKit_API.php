<?php
/**
 * ConvertKit API
 *
 * @package    ConvertKit
 * @subpackage ConvertKit_API
 * @author     ConvertKit
 */

namespace ConvertKit_API;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * ConvertKit API Class
 */
class ConvertKit_API
{
    /**
     * The SDK version.
     *
     * @var string
     */
    public const VERSION = '1.0.0';

    /**
     * ConvertKit API Key
     *
     * @var string
     */
    protected $api_key;

    /**
     * ConvertKit API Secret
     *
     * @var string
     */
    protected $api_secret;

    /**
     * Version of ConvertKit API
     *
     * @var string
     */
    protected $api_version = 'v3';

    /**
     * ConvertKit API URL
     *
     * @var string
     */
    protected $api_url_base = 'https://api.convertkit.com/';

    /**
     * API resources
     *
     * @var array
     */
    protected $resources = [];

    /**
     * Additional markup
     *
     * @var array
     */
    protected $markup = [];

    /**
     * Debug
     *
     * @var boolean
     */
    protected $debug;

    /**
     * Debug
     *
     * @var boolean
     */
    protected $debug_logger;

    /**
     * Guzzle Http Client
     *
     * @var object
     */
    protected $client;


    /**
     * Constructor for ConvertKitAPI instance
     *
     * @param string  $api_key    ConvertKit API Key.
     * @param string  $api_secret ConvertKit API Secret.
     * @param boolean $debug      Log requests to debugger.
     */
    public function __construct(string $api_key, string $api_secret, bool $debug = false)
    {
        $this->api_key    = $api_key;
        $this->api_secret = $api_secret;
        $this->debug      = $debug;

        // Specify a User-Agent for API requests.
        $this->client = new Client(
            [
                'headers' => [
                    'User-Agent' => 'ConvertKitPHPSDK/' . self::VERSION . ';PHP/' . phpversion(),
                ],
            ]
        );

        if ($debug) {
            $this->debug_logger = new Logger('ck-debug');
            $stream_handler     = new StreamHandler(__DIR__ . '/logs/debug.log', Logger::DEBUG);
            $this->debug_logger->pushHandler(
                $stream_handler // phpcs:ignore Squiz.Objects.ObjectInstantiation.NotAssigned
            );
        }
    }

    /**
     * Add an entry to monologger.
     *
     * @param string $message Message.
     *
     * @return void
     */
    private function create_log(string $message)
    {
        if ($this->debug) {
            $this->debug_logger->info($message);
        }
    }


    /**
     * Gets the current account
     *
     * @return false|mixed
     */
    public function get_account()
    {
        $request = $this->api_version . '/account';

        $options = [
            'api_secret' => $this->api_secret,
        ];

        $this->create_log(sprintf('GET account: %s, %s', $request, json_encode($options)));

        return $this->make_request($request, 'GET', $options);
    }


    /**
     * Gets all sequences
     *
     * @return false|mixed
     */
    public function get_sequences()
    {
        $request = $this->api_version . '/sequences';

        $options = [
            'api_key' => $this->api_key,
        ];

        $this->create_log(sprintf('GET sequences: %s, %s', $request, json_encode($options)));

        return $this->make_request($request, 'GET', $options);
    }


    /**
     * Gets subscribers to a sequence
     *
     * @param integer $sequence_id Sequence ID.
     * @param string  $sort_order  Sort Order (asc|desc).
     *
     * @return false|mixed
     */
    public function get_sequence_subscriptions(int $sequence_id, string $sort_order = 'asc')
    {
        $request = $this->api_version . sprintf('/sequences/%s/subscriptions', $sequence_id);

        $options = [
            'api_secret' => $this->api_secret,
            'sort_order' => $sort_order,
        ];

        $this->create_log(
            sprintf(
                'GET sequence subscriptions: %s, %s, %s',
                $request,
                json_encode($options),
                $sequence_id
            )
        );

        return $this->make_request($request, 'GET', $options);
    }


    /**
     * Adds a subscriber to a sequence by email address
     *
     * @param integer $sequence_id Sequence ID.
     * @param string  $email       Email Address.
     *
     * @return false|mixed
     */
    public function add_subscriber_to_sequence(int $sequence_id, string $email)
    {
        $request = $this->api_version . sprintf('/courses/%s/subscribe', $sequence_id);

        $options = [
            'api_key' => $this->api_key,
            'email'   => $email,
        ];

        $this->create_log(
            sprintf(
                'POST add subscriber to sequence: %s, %s, %s, %s',
                $request,
                json_encode($options),
                $sequence_id,
                $email
            )
        );

        return $this->make_request($request, 'POST', $options);
    }


    /**
     * Adds a tag to a subscriber
     *
     * @param integer $tag     Tag ID.
     * @param array   $options Array of user data.
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|object
     */
    public function add_tag(int $tag, array $options)
    {
        if (!is_int($tag) || !is_array($options)) {
            throw new \InvalidArgumentException();
        }

        $request = $this->api_version . sprintf('/tags/%s/subscribe', $tag);

        $options['api_key'] = $this->api_key;

        $this->create_log(sprintf('POST add tag: %s, %s, %s', $request, json_encode($options), $tag));

        return $this->make_request($request, 'POST', $options);
    }


    /**
     * Gets a resource index
     * Possible resources: forms, landing_pages, subscription_forms, tags
     *
     * GET /{$resource}/
     *
     * @param string $resource Resource type.
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return object API response
     */
    public function get_resources(string $resource)
    {
        if (!is_string($resource)) {
            throw new \InvalidArgumentException();
        }

        if (! array_key_exists($resource, $this->resources)) {
            $options = [
                'api_key'         => $this->api_key,
                'timeout'         => 10,
                'Accept-Encoding' => 'gzip',
            ];

            $request = sprintf('/%s/%s', $this->api_version, (($resource === 'landing_pages') ? 'forms' : $resource));

            $this->create_log(sprintf('GET request %s, %s', $request, json_encode($options)));

            $resources = $this->make_request($request, 'GET', $options);

            if (!$resources) {
                $this->create_log('No resources');
                $this->resources[$resource] = [
                    [
                        'id'   => '-2',
                        'name' => 'Error contacting API',
                    ],
                ];
            } else {
                $_resource = [];

                if ('forms' === $resource) {
                    $response = [];
                    if (isset($resources->forms)) {
                        $response = $resources->forms;
                    }

                    $this->create_log(sprintf('forms response %s', json_encode($response)));
                    foreach ($response as $form) {
                        if (isset($form->archived) && $form->archived) {
                            continue;
                        }

                        $_resource[] = $form;
                    }
                } else if ('landing_pages' === $resource) {
                    $response = [];
                    if (isset($resources->forms)) {
                        $response = $resources->forms;
                    }

                    $this->create_log(sprintf('landing_pages response %s', json_encode($response)));
                    foreach ($response as $landing_page) {
                        if ('hosted' === $landing_page->type) {
                            if (isset($landing_page->archived) && $landing_page->archived) {
                                continue;
                            }

                            $_resource[] = $landing_page;
                        }
                    }
                } else if ('subscription_forms' === $resource) {
                    $this->create_log('subscription_forms');
                    foreach ($resources as $mapping) {
                        if (isset($mapping->archived) && $mapping->archived) {
                            continue;
                        }

                        $_resource[$mapping->id] = $mapping->form_id;
                    }
                } else if ('tags' === $resource) {
                    $response = [];
                    if (isset($resources->tags)) {
                        $response = $resources->tags;
                    }

                    $this->create_log(sprintf('tags response %s', json_encode($response)));
                    foreach ($response as $tag) {
                        $_resource[] = $tag;
                    }
                }//end if

                $this->resources[$resource] = $_resource;
            }//end if
        }//end if

        return $this->resources[$resource];
    }


    /**
     * Adds a subscriber to a form.
     *
     * @param integer $form_id Form ID.
     * @param array   $options Array of user data (email, name).
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|object
     */
    public function form_subscribe(int $form_id, array $options)
    {
        if (!is_int($form_id) || !is_array($options)) {
            throw new \InvalidArgumentException();
        }

        $request = $this->api_version . sprintf('/forms/%s/subscribe', $form_id);

        $options['api_key'] = $this->api_key;

        $this->create_log(sprintf('POST form subscribe: %s, %s, %s', $request, json_encode($options), $form_id));

        return $this->make_request($request, 'POST', $options);
    }


    /**
     * Remove subscription from a form
     *
     * @param array $options Array of user data (email).
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|object
     */
    public function form_unsubscribe(array $options)
    {
        if (!is_array($options)) {
            throw new \InvalidArgumentException();
        }

        $request = $this->api_version . '/unsubscribe';

        $options['api_secret'] = $this->api_secret;

        $this->create_log(sprintf('PUT form unsubscribe: %s, %s', $request, json_encode($options)));

        return $this->make_request($request, 'PUT', $options);
    }


    /**
     * Get the ConvertKit subscriber ID associated with email address if it exists.
     * Return false if subscriber not found.
     *
     * @param string $email_address Email Address.
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|integer
     */
    public function get_subscriber_id(string $email_address)
    {
        if (!is_string($email_address) || !filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException();
        }

        $request = $this->api_version . '/subscribers';

        $options = [
            'api_secret'    => $this->api_secret,
            'status'        => 'all',
            'email_address' => $email_address,
        ];

        $this->create_log(
            sprintf(
                'GET subscriber id from all subscribers: %s, %s, %s',
                $request,
                json_encode($options),
                $email_address
            )
        );

        $subscribers = $this->make_request($request, 'GET', $options);

        if (!$subscribers) {
            $this->create_log('No subscribers');
            return false;
        }

        $subscriber_id = $this::check_if_subscriber_in_array($email_address, $subscribers->subscribers);

        if ($subscriber_id) {
            return $subscriber_id;
        }

        $this->create_log('Subscriber not found');

        return false;
    }


    /**
     * Get subscriber by id
     *
     * @param integer $subscriber_id Subscriber ID.
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|integer
     */
    public function get_subscriber(int $subscriber_id)
    {
        if (!is_int($subscriber_id) || $subscriber_id < 1) {
            throw new \InvalidArgumentException();
        }

        $request = $this->api_version . sprintf('/subscribers/%s', $subscriber_id);

        $options = [
            'api_secret' => $this->api_secret,
        ];

        $this->create_log(sprintf('GET subscriber tags: %s, %s, %s', $request, json_encode($options), $subscriber_id));

        return $this->make_request($request, 'GET', $options);
    }


    /**
     * Get a list of the tags for a subscriber.
     *
     * @param integer $subscriber_id Subscriber ID.
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|array
     */
    public function get_subscriber_tags(int $subscriber_id)
    {
        if (!is_int($subscriber_id) || $subscriber_id < 1) {
            throw new \InvalidArgumentException();
        }

        $request = $this->api_version . sprintf('/subscribers/%s/tags', $subscriber_id);

        $options = [
            'api_key' => $this->api_key,
        ];

        $this->create_log(sprintf('GET subscriber tags: %s, %s, %s', $request, json_encode($options), $subscriber_id));

        return $this->make_request($request, 'GET', $options);
    }


    /**
     * List purchases.
     *
     * @param array $options Request options.
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|object
     */
    public function list_purchases(array $options)
    {
        if (!is_array($options)) {
            throw new \InvalidArgumentException();
        }

        $request = $this->api_version . '/purchases';

        $options['api_secret'] = $this->api_secret;

        $this->create_log(sprintf('GET list purchases: %s, %s', $request, json_encode($options)));

        return $this->make_request($request, 'GET', $options);
    }


    /**
     * Creates a purchase.
     *
     * @param array $options Purchase data.
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|object
     */
    public function create_purchase(array $options)
    {
        if (!is_array($options)) {
            throw new \InvalidArgumentException();
        }

        $request = $this->api_version . '/purchases';

        $options['api_secret'] = $this->api_secret;

        $this->create_log(sprintf('POST create purchase: %s, %s', $request, json_encode($options)));

        return $this->make_request($request, 'POST', $options);
    }


    /**
     * Get markup from ConvertKit for the provided $url.
     *
     * Supports legacy forms and legacy landing pages.
     * Forms and Landing Pages should be embedded using the supplied JS embed script in
     * the API response when using get_resources().
     *
     * @param string $url URL of HTML page.
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|string
     */
    public function get_resource(string $url)
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException();
        }

        $resource = '';

        $this->create_log(sprintf('Getting resource %s', $url));

        // If the resource was already fetched, return the cached version now.
        if (isset($this->markup[$url])) {
            $this->create_log('Resource already set');
            return $this->markup[$url];
        }

        // Fetch the resource.
        $request  = new Request(
            'GET',
            $url,
            ['Accept-Encoding' => 'gzip']
        );
        $response = $this->client->send($request);

        // Fetch HTML.
        $body = $response->getBody()->getContents();

        // Forcibly tell DOMDocument that this HTML uses the UTF-8 charset.
        // <meta charset="utf-8"> isn't enough, as DOMDocument still interprets the HTML as ISO-8859,
        // which breaks character encoding.
        // Use of mb_convert_encoding() with HTML-ENTITIES is deprecated in PHP 8.2, so we have to use this method.
        // If we don't, special characters render incorrectly.
        $body = str_replace(
            '<head>',
            '<head>' . "\n" . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">',
            $body
        );

        // Get just the scheme and host from the URL.
        $url_scheme           = parse_url($url);
        $url_scheme_host_only = $url_scheme['scheme'] . '://' . $url_scheme['host'];

        // Load the HTML into a DOMDocument.
        libxml_use_internal_errors(true);
        $html = new \DOMDocument();
        $html->loadHTML($body);

        // Convert any relative URLs to absolute URLs in the HTML DOM.
        $this->convert_relative_to_absolute_urls($html->getElementsByTagName('a'), 'href', $url_scheme_host_only);
        $this->convert_relative_to_absolute_urls($html->getElementsByTagName('link'), 'href', $url_scheme_host_only);
        $this->convert_relative_to_absolute_urls($html->getElementsByTagName('img'), 'src', $url_scheme_host_only);
        $this->convert_relative_to_absolute_urls($html->getElementsByTagName('script'), 'src', $url_scheme_host_only);
        $this->convert_relative_to_absolute_urls($html->getElementsByTagName('form'), 'action', $url_scheme_host_only);

        // Remove some HTML tags that DOMDocument adds, returning the output.
        // We do this instead of using LIBXML_HTML_NOIMPLIED in loadHTML(), because Legacy Forms
        // are not always contained in a single root / outer element, which is required for
        // LIBXML_HTML_NOIMPLIED to correctly work.
        $resource = $this->strip_html_head_body_tags($html->saveHTML());

        // Cache and return.
        $this->markup[$url] = $resource;
        return $resource;
    }


    /**
     * Converts any relative URls to absolute, fully qualified HTTP(s) URLs for the given
     * DOM Elements.
     *
     * @param \DOMNodeList $elements  Elements.
     * @param string       $attribute HTML Attribute.
     * @param string       $url       Absolute URL to prepend to relative URLs.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function convert_relative_to_absolute_urls(\DOMNodeList $elements, string $attribute, string $url)
    {
        // Anchor hrefs.
        foreach ($elements as $element) {
            // Skip if the attribute's value is empty.
            if (empty($element->getAttribute($attribute))) {
                continue;
            }

            // Skip if the attribute's value is a fully qualified URL.
            if (filter_var($element->getAttribute($attribute), FILTER_VALIDATE_URL)) {
                continue;
            }

            // Skip if this is a Google Font CSS URL.
            if (strpos($element->getAttribute($attribute), '//fonts.googleapis.com') !== false) {
                continue;
            }

            // If here, the attribute's value is a relative URL, missing the http(s) and domain.
            // Prepend the URL to the attribute's value.
            $element->setAttribute($attribute, $url . $element->getAttribute($attribute));
        }
    }


    /**
     * Strips <html>, <head> and <body> opening and closing tags from the given markup,
     * as well as the Content-Type meta tag we might have added in get_html().
     *
     * @param string $markup HTML Markup.
     *
     * @since 1.0.0
     *
     * @return string              HTML Markup
     */
    private function strip_html_head_body_tags(string $markup)
    {
        $markup = str_replace('<html>', '', $markup);
        $markup = str_replace('</html>', '', $markup);
        $markup = str_replace('<head>', '', $markup);
        $markup = str_replace('</head>', '', $markup);
        $markup = str_replace('<body>', '', $markup);
        $markup = str_replace('</body>', '', $markup);
        $markup = str_replace('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', '', $markup);

        return $markup;
    }


    /**
     * Performs an API request using Guzzle.
     *
     * @param string $endpoint API Endpoint.
     * @param string $method   Request method (POST, GET, PUT, PATCH, DELETE).
     * @param array  $args     Request arguments.
     *
     * @throws \InvalidArgumentException If the provided arguments are not of the expected type.
     *
     * @return false|mixed
     */
    public function make_request(string $endpoint, string $method, array $args = [])
    {
        if (!is_string($endpoint) || !is_string($method) || !is_array($args)) {
            throw new \InvalidArgumentException();
        }

        $url = $this->api_url_base . $endpoint;

        $this->create_log(sprintf('Making request on %s.', $url));

        $request_body = json_encode($args);

        $this->create_log(sprintf('%s, Request body: %s', $method, $request_body));

        if ($method === 'GET') {
            if ($args) {
                $url .= '?' . http_build_query($args);
            }

            $request = new Request($method, $url);
        } else {
            $request = new Request(
                $method,
                $url,
                [
                    'Content-Type'   => 'application/json',
                    'Content-Length' => strlen($request_body),
                ],
                $request_body
            );
        }

        $response = $this->client->send(
            $request,
            ['exceptions' => false]
        );

        $status_code = $response->getStatusCode();

        // If not between 200 and 300.
        if (!preg_match('/^[2-3][0-9]{2}/', $status_code)) {
            $this->create_log(sprintf('Response code is %s.', $status_code));
            return false;
        }

        $response_body = json_decode($response->getBody()->getContents());

        if ($response_body) {
            $this->create_log('Finish request successfully.');
            return $response_body;
        }

        $this->create_log('Failed to finish request.');
        return false;
    }


    /**
     * Looks for subscriber with email in array
     *
     * @param string $email_address Email Address.
     * @param array  $subscribers   Subscribers.
     *
     * @return false|integer  false if not found, else subscriber object
     */
    private function check_if_subscriber_in_array(string $email_address, array $subscribers)
    {
        foreach ($subscribers as $subscriber) {
            if ($subscriber->email_address === $email_address) {
                $this->create_log('Subscriber found!');
                return $subscriber->id;
            }
        }

        $this->create_log('Subscriber not found on current page.');
        return false;
    }
}
