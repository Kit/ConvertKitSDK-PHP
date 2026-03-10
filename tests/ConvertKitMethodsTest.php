<?php

use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;
use ConvertKit_API\ConvertKit_API;

/**
 * Test methods in ConvertKit_API_Traits that don't interact with the API,
 * such as convert_relative_to_absolute_urls().
 */
class ConvertKitMethodsTest extends TestCase
{
    /**
     * Kit API Class
     *
     * @var object
     */
    protected $api;

    /**
     * Initialize the API class before each test.
     *
     * @since   2.4.1
     *
     * @return  void
     */
    protected function setUp(): void
    {
        $this->api = new ConvertKit_API();
    }

    /**
     * Test the convert_relative_to_absolute_urls() method.
     *
     * @since   2.4.1
     *
     * @return  void
     */
    public function testConvertRelativeToAbsoluteUrls()
    {
        // Setup HTML in DOMDocument.
        $html = new \DOMDocument();
        $html->loadHTML('<html>
            <head>
                <script type="text/javascript" src="rocket-loader.min.js"></script>
                <link rel="stylesheet" href="//fonts.googleapis.com">
            </head>
            <body>
                <a href="/test">Test</a>
                <img src="/test.jpg" />
                <script type="text/javascript" src="/test.js"></script>
                <form action="/test">Test</form>
            </body>
        </html>');

        // Define URL to prepend to relative URLs.
        $url_scheme_host_only = 'https://example.com';

        // Convert relative URLs to absolute URLs for elements we want to test.
        $this->api->convert_relative_to_absolute_urls($html->getElementsByTagName('a'), 'href', $url_scheme_host_only);
        $this->api->convert_relative_to_absolute_urls($html->getElementsByTagName('link'), 'href', $url_scheme_host_only);
        $this->api->convert_relative_to_absolute_urls($html->getElementsByTagName('img'), 'src', $url_scheme_host_only);
        $this->api->convert_relative_to_absolute_urls($html->getElementsByTagName('script'), 'src', $url_scheme_host_only);
        $this->api->convert_relative_to_absolute_urls($html->getElementsByTagName('form'), 'action', $url_scheme_host_only);

        // Fetch HTML string.
        $output = $html->saveHTML();

        // Assert string contains expected HTML elements that should not be modified.
        $this->assertStringContainsString('<link rel="stylesheet" href="//fonts.googleapis.com">', $output);

        // Assert string does not contain HTML elements that should be removed.
        $this->assertStringNotContainsString('<script type="text/javascript" src="rocket-loader.min.js"></script>', $output);

        // Assert string contains expected HTML elements that should be modified.
        $this->assertStringContainsString('<a href="' . $url_scheme_host_only . '/test">Test</a>', $output);
        $this->assertStringContainsString('<img src="' . $url_scheme_host_only . '/test.jpg">', $output);
        $this->assertStringContainsString('<script type="text/javascript" src="' . $url_scheme_host_only . '/test.js"></script>', $output);
        $this->assertStringContainsString('<form action="' . $url_scheme_host_only . '/test">Test</form>', $output);
    }

    /**
	 * Test that the get_body_html() method returns the expected HTML.
	 *
	 * @since   2.4.1
	 */
    public function testGetBodyHtml()
    {
        $content  = '<h1>Vantar þinn ungling sjálfstraust í stærðfræði?</h1><p>This is a test</p>';
        $html = new \DOMDocument();
        $html->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$content.'</body></html>');
        $this->assertEquals($content, $this->api->get_body_html($html));
    }
}
