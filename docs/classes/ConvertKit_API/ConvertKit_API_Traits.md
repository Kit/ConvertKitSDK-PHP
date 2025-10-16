
ConvertKit API Traits

***

* Full name: `\ConvertKit_API\ConvertKit_API_Traits`

## Properties

### client_id

ConvertKit OAuth Application Client ID

```php
protected string $client_id
```

***
### client_secret

ConvertKit OAuth Application Client Secret

```php
protected string $client_secret
```

***
### access_token

Access Token

```php
protected string $access_token
```

***
### api_key

API Key

```php
protected string $api_key
```

***
### oauth_authorize_url

OAuth Authorization URL

```php
protected string $oauth_authorize_url
```

***
### oauth_token_url

OAuth Token URL

```php
protected string $oauth_token_url
```

***
### api_version

Version of ConvertKit API

```php
protected string $api_version
```

***
### api_url_base

ConvertKit API URL

```php
protected string $api_url_base
```

***

## Methods

### get_account

Gets the current account

```php
public get_account(): false|mixed
```

**See Also:**

* https://developers.convertkit.com/v4.html#get-current-account

***
### get_account_colors

Gets the account's colors

```php
public get_account_colors(): false|mixed
```

**See Also:**

* https://developers.convertkit.com/v4.html#list-colors

***
### update_account_colors

Gets the account's colors

```php
public update_account_colors(array<string,string> $colors): false|mixed
```

**Parameters:**

| Parameter | Type                     | Description |
|-----------|--------------------------|-------------|
| `$colors` | **array<string,string>** | Hex colors. |

**See Also:**

* https://developers.convertkit.com/v4.html#list-colors

***
### get_creator_profile

Gets the Creator Profile

```php
public get_creator_profile(): false|mixed
```

**See Also:**

* https://developers.convertkit.com/v4.html#get-creator-profile

***
### get_email_stats

Gets email stats

```php
public get_email_stats(): false|mixed
```

**See Also:**

* https://developers.convertkit.com/v4.html#get-email-stats

***
### get_growth_stats

Gets growth stats

```php
public get_growth_stats(\DateTime|null $starting = null, \DateTime|null $ending = null): false|mixed
```

**Parameters:**

| Parameter   | Type                | Description                                                                 |
|-------------|---------------------|-----------------------------------------------------------------------------|
| `$starting` | **\DateTime\|null** | Gets stats for time period beginning on this date. Defaults to 90 days ago. |
| `$ending`   | **\DateTime\|null** | Gets stats for time period ending on this date. Defaults to today.          |

**See Also:**

* https://developers.convertkit.com/v4.html#get-growth-stats

***
### get_forms

Get forms.

```php
public get_forms(string $status = 'active', bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): mixed|array<int,\stdClass>
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$status`              | **string** | Form status (active\|archived\|trashed\|all).                    |
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#convertkit-api-forms

***
### get_landing_pages

Get landing pages.

```php
public get_landing_pages(string $status = 'active', bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): mixed|array<int,\stdClass>
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$status`              | **string** | Form status (active\|archived\|trashed\|all).                    |
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#convertkit-api-forms

***
### add_subscribers_to_forms

Adds subscribers to forms in bulk.

```php
public add_subscribers_to_forms(array<string,string>[] $forms_subscribers_ids, string $callback_url = ''): mixed|object
```

**Parameters:**

| Parameter                | Type                       | Description                                                                           |
|--------------------------|----------------------------|---------------------------------------------------------------------------------------|
| `$forms_subscribers_ids` | **array<string,string>[]** | Array of arrays comprising of `form_id`, `subscriber_id` and optional `referrer` URL. |
| `$callback_url`          | **string**                 | URL to notify for large batch size when async processing complete.                    |

**See Also:**

* https://developers.kit.com/v4.html#bulk-add-subscribers-to-forms

***
### add_subscriber_to_form_by_email

Adds a subscriber to a form by email address

```php
public add_subscriber_to_form_by_email(int $form_id, string $email_address, string $referrer = ''): false|mixed
```

**Parameters:**

| Parameter        | Type       | Description    |
|------------------|------------|----------------|
| `$form_id`       | **int**    | Form ID.       |
| `$email_address` | **string** | Email Address. |
| `$referrer`      | **string** | Referrer.      |

**See Also:**

* https://developers.convertkit.com/v4.html#add-subscriber-to-form-by-email-address

***
### add_subscriber_to_form

Adds a subscriber to a form by subscriber ID

```php
public add_subscriber_to_form(int $form_id, int $subscriber_id, string $referrer = ''): false|mixed
```

**Parameters:**

| Parameter        | Type       | Description    |
|------------------|------------|----------------|
| `$form_id`       | **int**    | Form ID.       |
| `$subscriber_id` | **int**    | Subscriber ID. |
| `$referrer`      | **string** | Referrer URL.  |

**See Also:**

* https://developers.convertkit.com/v4.html#add-subscriber-to-form

***
### get_form_subscriptions

List subscribers for a form

```php
public get_form_subscriptions(int $form_id, string $subscriber_state = 'active', \DateTime|null $created_after = null, \DateTime|null $created_before = null, \DateTime|null $added_after = null, \DateTime|null $added_before = null, bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type                | Description                                                          |
|------------------------|---------------------|----------------------------------------------------------------------|
| `$form_id`             | **int**             | Form ID.                                                             |
| `$subscriber_state`    | **string**          | Subscriber State (active\|bounced\|cancelled\|complained\|inactive). |
| `$created_after`       | **\DateTime\|null** | Filter subscribers who have been created after this date.            |
| `$created_before`      | **\DateTime\|null** | Filter subscribers who have been created before this date.           |
| `$added_after`         | **\DateTime\|null** | Filter subscribers who have been added to the form after this date.  |
| `$added_before`        | **\DateTime\|null** | Filter subscribers who have been added to the form before this date. |
| `$include_total_count` | **bool**            | To include the total count of records in the response, use true.     |
| `$after_cursor`        | **string**          | Return results after the given pagination cursor.                    |
| `$before_cursor`       | **string**          | Return results before the given pagination cursor.                   |
| `$per_page`            | **int**             | Number of results to return.                                         |

**See Also:**

* https://developers.convertkit.com/v4.html#list-subscribers-for-a-form

***
### get_sequences

Gets sequences

```php
public get_sequences(bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#list-sequences

***
### add_subscriber_to_sequence_by_email

Adds a subscriber to a sequence by email address

```php
public add_subscriber_to_sequence_by_email(int $sequence_id, string $email_address): false|mixed
```

**Parameters:**

| Parameter        | Type       | Description    |
|------------------|------------|----------------|
| `$sequence_id`   | **int**    | Sequence ID.   |
| `$email_address` | **string** | Email Address. |

**See Also:**

* https://developers.convertkit.com/v4.html#add-subscriber-to-sequence-by-email-address

***
### add_subscriber_to_sequence

Adds a subscriber to a sequence by subscriber ID

```php
public add_subscriber_to_sequence(int $sequence_id, int $subscriber_id): false|mixed
```

**Parameters:**

| Parameter        | Type    | Description    |
|------------------|---------|----------------|
| `$sequence_id`   | **int** | Sequence ID.   |
| `$subscriber_id` | **int** | Subscriber ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#add-subscriber-to-sequence

***
### get_sequence_subscriptions

List subscribers for a sequence

```php
public get_sequence_subscriptions(int $sequence_id, string $subscriber_state = 'active', \DateTime|null $created_after = null, \DateTime|null $created_before = null, \DateTime|null $added_after = null, \DateTime|null $added_before = null, bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type                | Description                                                          |
|------------------------|---------------------|----------------------------------------------------------------------|
| `$sequence_id`         | **int**             | Sequence ID.                                                         |
| `$subscriber_state`    | **string**          | Subscriber State (active\|bounced\|cancelled\|complained\|inactive). |
| `$created_after`       | **\DateTime\|null** | Filter subscribers who have been created after this date.            |
| `$created_before`      | **\DateTime\|null** | Filter subscribers who have been created before this date.           |
| `$added_after`         | **\DateTime\|null** | Filter subscribers who have been added to the form after this date.  |
| `$added_before`        | **\DateTime\|null** | Filter subscribers who have been added to the form before this date. |
| `$include_total_count` | **bool**            | To include the total count of records in the response, use true.     |
| `$after_cursor`        | **string**          | Return results after the given pagination cursor.                    |
| `$before_cursor`       | **string**          | Return results before the given pagination cursor.                   |
| `$per_page`            | **int**             | Number of results to return.                                         |

**See Also:**

* https://developers.convertkit.com/v4.html#list-subscribers-for-a-sequence

***
### get_tags

List tags.

```php
public get_tags(bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): mixed|array<int,\stdClass>
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#list-tags

***
### create_tag

Creates a tag.

```php
public create_tag(string $tag): false|mixed
```

**Parameters:**

| Parameter | Type       | Description |
|-----------|------------|-------------|
| `$tag`    | **string** | Tag Name.   |

**See Also:**

* https://developers.convertkit.com/v4.html#create-a-tag

***
### create_tags

Creates multiple tags.

```php
public create_tags(array<int,string> $tags, string $callback_url = ''): false|mixed
```

**Parameters:**

| Parameter       | Type                  | Description                                                        |
|-----------------|-----------------------|--------------------------------------------------------------------|
| `$tags`         | **array<int,string>** | Tag Names.                                                         |
| `$callback_url` | **string**            | URL to notify for large batch size when async processing complete. |

**See Also:**

* https://developers.convertkit.com/v4.html#bulk-create-tags

***
### tag_subscriber_by_email

Tags a subscriber with the given existing Tag.

```php
public tag_subscriber_by_email(int $tag_id, string $email_address): false|mixed
```

**Parameters:**

| Parameter        | Type       | Description    |
|------------------|------------|----------------|
| `$tag_id`        | **int**    | Tag ID.        |
| `$email_address` | **string** | Email Address. |

**See Also:**

* https://developers.convertkit.com/v4.html#tag-a-subscriber-by-email-address

***
### tag_subscriber

Tags a subscriber by subscriber ID with the given existing Tag.

```php
public tag_subscriber(int $tag_id, int $subscriber_id): false|mixed
```

**Parameters:**

| Parameter        | Type    | Description    |
|------------------|---------|----------------|
| `$tag_id`        | **int** | Tag ID.        |
| `$subscriber_id` | **int** | Subscriber ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#tag-a-subscriber

***
### remove_tag_from_subscriber

Removes a tag from a subscriber.

```php
public remove_tag_from_subscriber(int $tag_id, int $subscriber_id): false|mixed
```

**Parameters:**

| Parameter        | Type    | Description    |
|------------------|---------|----------------|
| `$tag_id`        | **int** | Tag ID.        |
| `$subscriber_id` | **int** | Subscriber ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#remove-tag-from-subscriber

***
### remove_tag_from_subscriber_by_email

Removes a tag from a subscriber by email address.

```php
public remove_tag_from_subscriber_by_email(int $tag_id, string $email_address): false|mixed
```

**Parameters:**

| Parameter        | Type       | Description               |
|------------------|------------|---------------------------|
| `$tag_id`        | **int**    | Tag ID.                   |
| `$email_address` | **string** | Subscriber email address. |

**See Also:**

* https://developers.convertkit.com/v4.html#remove-tag-from-subscriber-by-email-address

***
### get_tag_subscriptions

List subscribers for a tag

```php
public get_tag_subscriptions(int $tag_id, string $subscriber_state = 'active', \DateTime|null $created_after = null, \DateTime|null $created_before = null, \DateTime|null $tagged_after = null, \DateTime|null $tagged_before = null, bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type                | Description                                                          |
|------------------------|---------------------|----------------------------------------------------------------------|
| `$tag_id`              | **int**             | Tag ID.                                                              |
| `$subscriber_state`    | **string**          | Subscriber State (active\|bounced\|cancelled\|complained\|inactive). |
| `$created_after`       | **\DateTime\|null** | Filter subscribers who have been created after this date.            |
| `$created_before`      | **\DateTime\|null** | Filter subscribers who have been created before this date.           |
| `$tagged_after`        | **\DateTime\|null** | Filter subscribers who have been tagged after this date.             |
| `$tagged_before`       | **\DateTime\|null** | Filter subscribers who have been tagged before this date.            |
| `$include_total_count` | **bool**            | To include the total count of records in the response, use true.     |
| `$after_cursor`        | **string**          | Return results after the given pagination cursor.                    |
| `$before_cursor`       | **string**          | Return results before the given pagination cursor.                   |
| `$per_page`            | **int**             | Number of results to return.                                         |

**See Also:**

* https://developers.convertkit.com/v4.html#list-subscribers-for-a-tag

***
### get_email_templates

List email templates.

```php
public get_email_templates(bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#convertkit-api-email-templates

***
### get_subscribers

List subscribers.

```php
public get_subscribers(string $subscriber_state = 'active', string $email_address = '', \DateTime|null $created_after = null, \DateTime|null $created_before = null, \DateTime|null $updated_after = null, \DateTime|null $updated_before = null, string $sort_field = 'id', string $sort_order = 'desc', bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type                | Description                                                          |
|------------------------|---------------------|----------------------------------------------------------------------|
| `$subscriber_state`    | **string**          | Subscriber State (active\|bounced\|cancelled\|complained\|inactive). |
| `$email_address`       | **string**          | Search susbcribers by email address. This is an exact match search.  |
| `$created_after`       | **\DateTime\|null** | Filter subscribers who have been created after this date.            |
| `$created_before`      | **\DateTime\|null** | Filter subscribers who have been created before this date.           |
| `$updated_after`       | **\DateTime\|null** | Filter subscribers who have been updated after this date.            |
| `$updated_before`      | **\DateTime\|null** | Filter subscribers who have been updated before this date.           |
| `$sort_field`          | **string**          | Sort Field (id\|updated_at\|cancelled_at).                           |
| `$sort_order`          | **string**          | Sort Order (asc\|desc).                                              |
| `$include_total_count` | **bool**            | To include the total count of records in the response, use true.     |
| `$after_cursor`        | **string**          | Return results after the given pagination cursor.                    |
| `$before_cursor`       | **string**          | Return results before the given pagination cursor.                   |
| `$per_page`            | **int**             | Number of results to return.                                         |

**See Also:**

* https://developers.convertkit.com/v4.html#list-subscribers

***
### create_subscriber

Create a subscriber.

```php
public create_subscriber(string $email_address, string $first_name = '', string $subscriber_state = '', array<string,string> $fields = []): mixed
```

Behaves as an upsert. If a subscriber with the provided email address does not exist,
it creates one with the specified first name and state. If a subscriber with the provided
email address already exists, it updates the first name.

**Parameters:**

| Parameter           | Type                     | Description                                                          |
|---------------------|--------------------------|----------------------------------------------------------------------|
| `$email_address`    | **string**               | Email Address.                                                       |
| `$first_name`       | **string**               | First Name.                                                          |
| `$subscriber_state` | **string**               | Subscriber State (active\|bounced\|cancelled\|complained\|inactive). |
| `$fields`           | **array<string,string>** | Custom Fields.                                                       |

**See Also:**

* https://developers.convertkit.com/v4.html#create-a-subscriber

***
### create_subscribers

Create multiple subscribers.

```php
public create_subscribers(array<int,array<string,string>> $subscribers, string $callback_url = ''): mixed
```

**Parameters:**

| Parameter       | Type                                | Description                                                        |
|-----------------|-------------------------------------|--------------------------------------------------------------------|
| `$subscribers`  | **array<int,array<string,string>>** | Subscribers.                                                       |
| `$callback_url` | **string**                          | URL to notify for large batch size when async processing complete. |

**See Also:**

* https://developers.convertkit.com/v4.html#bulk-create-subscribers

***
### get_subscriber_id

Get the ConvertKit subscriber ID associated with email address if it exists.

```php
public get_subscriber_id(string $email_address): false|int
```

Return false if subscriber not found.

**Parameters:**

| Parameter        | Type       | Description    |
|------------------|------------|----------------|
| `$email_address` | **string** | Email Address. |

**Throws:**

If the email address is not a valid email format.
- [`InvalidArgumentException`](../InvalidArgumentException)

**See Also:**

* https://developers.convertkit.com/v4.html#get-a-subscriber

***
### get_subscriber

Get subscriber by id

```php
public get_subscriber(int $subscriber_id): mixed|int
```

**Parameters:**

| Parameter        | Type    | Description    |
|------------------|---------|----------------|
| `$subscriber_id` | **int** | Subscriber ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#get-a-subscriber

***
### update_subscriber

Updates the information for a single subscriber.

```php
public update_subscriber(int $subscriber_id, string $first_name = '', string $email_address = '', array<string,string> $fields = []): mixed
```

**Parameters:**

| Parameter        | Type                     | Description             |
|------------------|--------------------------|-------------------------|
| `$subscriber_id` | **int**                  | Existing Subscriber ID. |
| `$first_name`    | **string**               | New First Name.         |
| `$email_address` | **string**               | New Email Address.      |
| `$fields`        | **array<string,string>** | Updated Custom Fields.  |

**See Also:**

* https://developers.convertkit.com/v4.html#update-a-subscriber

***
### unsubscribe_by_email

Unsubscribe an email address.

```php
public unsubscribe_by_email(string $email_address): mixed|object
```

**Parameters:**

| Parameter        | Type       | Description    |
|------------------|------------|----------------|
| `$email_address` | **string** | Email Address. |

**See Also:**

* https://developers.convertkit.com/v4.html#unsubscribe-subscriber

***
### unsubscribe

Unsubscribe the given subscriber ID.

```php
public unsubscribe(int $subscriber_id): mixed|object
```

**Parameters:**

| Parameter        | Type    | Description    |
|------------------|---------|----------------|
| `$subscriber_id` | **int** | Subscriber ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#unsubscribe-subscriber

***
### get_subscriber_tags

Get a list of the tags for a subscriber.

```php
public get_subscriber_tags(int $subscriber_id, bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): mixed|array<int,\stdClass>
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$subscriber_id`       | **int**    | Subscriber ID.                                                   |
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#list-tags-for-a-subscriber

***
### get_broadcasts

List broadcasts.

```php
public get_broadcasts(bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#list-broadcasts

***
### create_broadcast

Creates a broadcast.

```php
public create_broadcast(string $subject = '', string $content = '', string $description = '', bool $public = false, \DateTime|null $published_at = null, \DateTime|null $send_at = null, string $email_address = '', string $email_template_id = '', string $thumbnail_alt = '', string $thumbnail_url = '', string $preview_text = '', array<string,string> $subscriber_filter = []): mixed|object
```

**Parameters:**

| Parameter            | Type                     | Description                                                                                                                                                               |
|----------------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$subject`           | **string**               | The broadcast email's subject.                                                                                                                                            |
| `$content`           | **string**               | The broadcast's email HTML content.                                                                                                                                       |
| `$description`       | **string**               | An internal description of this broadcast.                                                                                                                                |
| `$public`            | **bool**                 | Specifies whether or not this is a public post.                                                                                                                           |
| `$published_at`      | **\DateTime\|null**      | Specifies the time that this post was published (applicable
only to public posts).                                                                                        |
| `$send_at`           | **\DateTime\|null**      | Time that this broadcast should be sent; leave blank to create
a draft broadcast. If set to a future time, this is the time that
the broadcast will be scheduled to send. |
| `$email_address`     | **string**               | Sending email address; leave blank to use your account's
default sending email address.                                                                                   |
| `$email_template_id` | **string**               | ID of the email template to use; leave blank to use your
account's default email template.                                                                                |
| `$thumbnail_alt`     | **string**               | Specify the ALT attribute of the public thumbnail image
(applicable only to public posts).                                                                                |
| `$thumbnail_url`     | **string**               | Specify the URL of the thumbnail image to accompany the broadcast
post (applicable only to public posts).                                                                 |
| `$preview_text`      | **string**               | Specify the preview text of the email.                                                                                                                                    |
| `$subscriber_filter` | **array<string,string>** | Filter subscriber(s) to send the email to.                                                                                                                                |

**See Also:**

* https://developers.convertkit.com/v4.html#create-a-broadcast

***
### get_broadcast

Retrieve a specific broadcast.

```php
public get_broadcast(int $id): mixed|object
```

**Parameters:**

| Parameter | Type    | Description   |
|-----------|---------|---------------|
| `$id`     | **int** | Broadcast ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#get-a-broadcast

***
### get_broadcast_stats

Get the statistics (recipient count, open rate, click rate, unsubscribe count,
total clicks, status, and send progress) for a specific broadcast.

```php
public get_broadcast_stats(int $id): mixed|object
```

**Parameters:**

| Parameter | Type    | Description   |
|-----------|---------|---------------|
| `$id`     | **int** | Broadcast ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#get-stats

***
### update_broadcast

Updates a broadcast.

```php
public update_broadcast(int $id, string $subject = '', string $content = '', string $description = '', bool $public = false, \DateTime|null $published_at = null, \DateTime|null $send_at = null, string $email_address = '', string $email_template_id = '', string $thumbnail_alt = '', string $thumbnail_url = '', string $preview_text = '', array<string,string> $subscriber_filter = []): mixed|object
```

**Parameters:**

| Parameter            | Type                     | Description                                                                                                                                                               |
|----------------------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `$id`                | **int**                  | Broadcast ID.                                                                                                                                                             |
| `$subject`           | **string**               | The broadcast email's subject.                                                                                                                                            |
| `$content`           | **string**               | The broadcast's email HTML content.                                                                                                                                       |
| `$description`       | **string**               | An internal description of this broadcast.                                                                                                                                |
| `$public`            | **bool**                 | Specifies whether or not this is a public post.                                                                                                                           |
| `$published_at`      | **\DateTime\|null**      | Specifies the time that this post was published (applicable
only to public posts).                                                                                        |
| `$send_at`           | **\DateTime\|null**      | Time that this broadcast should be sent; leave blank to create
a draft broadcast. If set to a future time, this is the time that
the broadcast will be scheduled to send. |
| `$email_address`     | **string**               | Sending email address; leave blank to use your account's
default sending email address.                                                                                   |
| `$email_template_id` | **string**               | ID of the email template to use; leave blank to use your
account's default email template.                                                                                |
| `$thumbnail_alt`     | **string**               | Specify the ALT attribute of the public thumbnail image
(applicable only to public posts).                                                                                |
| `$thumbnail_url`     | **string**               | Specify the URL of the thumbnail image to accompany the broadcast
post (applicable only to public posts).                                                                 |
| `$preview_text`      | **string**               | Specify the preview text of the email.                                                                                                                                    |
| `$subscriber_filter` | **array<string,string>** | Filter subscriber(s) to send the email to.                                                                                                                                |

**See Also:**

* https://developers.convertkit.com/#create-a-broadcast

***
### delete_broadcast

Deletes an existing broadcast.

```php
public delete_broadcast(int $id): mixed|object
```

**Parameters:**

| Parameter | Type    | Description   |
|-----------|---------|---------------|
| `$id`     | **int** | Broadcast ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#delete-a-broadcast

***
### get_webhooks

List webhooks.

```php
public get_webhooks(bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#list-webhooks

***
### create_webhook

Creates a webhook that will be called based on the chosen event types.

```php
public create_webhook(string $url, string $event, string $parameter = ''): mixed|object
```

**Parameters:**

| Parameter    | Type       | Description                                |
|--------------|------------|--------------------------------------------|
| `$url`       | **string** | URL to receive event.                      |
| `$event`     | **string** | Event to subscribe to.                     |
| `$parameter` | **string** | Optional parameter depending on the event. |

**Throws:**

If the event is not supported.
- [`InvalidArgumentException`](../InvalidArgumentException)

**See Also:**

* https://developers.convertkit.com/v4.html#create-a-webhook

***
### delete_webhook

Deletes an existing webhook.

```php
public delete_webhook(int $id): mixed|object
```

**Parameters:**

| Parameter | Type    | Description |
|-----------|---------|-------------|
| `$id`     | **int** | Webhook ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#delete-a-webhook

***
### get_custom_fields

List custom fields.

```php
public get_custom_fields(bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#list-custom-fields

***
### create_custom_field

Creates a custom field.

```php
public create_custom_field(string $label): mixed|object
```

**Parameters:**

| Parameter | Type       | Description         |
|-----------|------------|---------------------|
| `$label`  | **string** | Custom Field label. |

**See Also:**

* https://developers.convertkit.com/v4.html#create-a-custom-field

***
### create_custom_fields

Creates multiple custom fields.

```php
public create_custom_fields(string[] $labels, string $callback_url = ''): mixed|object
```

**Parameters:**

| Parameter       | Type         | Description                                                        |
|-----------------|--------------|--------------------------------------------------------------------|
| `$labels`       | **string[]** | Custom Fields labels.                                              |
| `$callback_url` | **string**   | URL to notify for large batch size when async processing complete. |

**See Also:**

* https://developers.convertkit.com/v4.html#bulk-create-custom-fields

***
### update_custom_field

Updates an existing custom field.

```php
public update_custom_field(int $id, string $label): mixed|object
```

**Parameters:**

| Parameter | Type       | Description                 |
|-----------|------------|-----------------------------|
| `$id`     | **int**    | Custom Field ID.            |
| `$label`  | **string** | Updated Custom Field label. |

**See Also:**

* https://developers.convertkit.com/v4.html#update-a-custom-field

***
### delete_custom_field

Deletes an existing custom field.

```php
public delete_custom_field(int $id): mixed|object
```

**Parameters:**

| Parameter | Type    | Description      |
|-----------|---------|------------------|
| `$id`     | **int** | Custom Field ID. |

**See Also:**

* https://developers.convertkit.com/#destroy-field

***
### get_purchases

List purchases.

```php
public get_purchases(bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#list-purchases

***
### get_purchase

Retuns a specific purchase.

```php
public get_purchase(int $purchase_id): mixed|object
```

**Parameters:**

| Parameter      | Type    | Description  |
|----------------|---------|--------------|
| `$purchase_id` | **int** | Purchase ID. |

**See Also:**

* https://developers.convertkit.com/v4.html#get-a-purchase

***
### create_purchase

Creates a purchase.

```php
public create_purchase(string $email_address, string $transaction_id, array<string,int|float|string> $products, string $currency = 'USD', string|null $first_name = null, string|null $status = null, float $subtotal, float $tax, float $shipping, float $discount, float $total, \DateTime|null $transaction_time = null): mixed|object
```

**Parameters:**

| Parameter           | Type                                 | Description                |
|---------------------|--------------------------------------|----------------------------|
| `$email_address`    | **string**                           | Email Address.             |
| `$transaction_id`   | **string**                           | Transaction ID.            |
| `$products`         | **array<string,int\|float\|string>** | Products.                  |
| `$currency`         | **string**                           | ISO Currency Code.         |
| `$first_name`       | **string\|null**                     | First Name.                |
| `$status`           | **string\|null**                     | Order Status.              |
| `$subtotal`         | **float**                            | Subtotal.                  |
| `$tax`              | **float**                            | Tax.                       |
| `$shipping`         | **float**                            | Shipping.                  |
| `$discount`         | **float**                            | Discount.                  |
| `$total`            | **float**                            | Total.                     |
| `$transaction_time` | **\DateTime\|null**                  | Transaction date and time. |

**See Also:**

* https://developers.convertkit.com/v4.html#create-a-purchase

***
### get_segments

List segments.

```php
public get_segments(bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): false|mixed
```

**Parameters:**

| Parameter              | Type       | Description                                                      |
|------------------------|------------|------------------------------------------------------------------|
| `$include_total_count` | **bool**   | To include the total count of records in the response, use true. |
| `$after_cursor`        | **string** | Return results after the given pagination cursor.                |
| `$before_cursor`       | **string** | Return results before the given pagination cursor.               |
| `$per_page`            | **int**    | Number of results to return.                                     |

**See Also:**

* https://developers.convertkit.com/v4.html#convertkit-api-segments

***
### convert_relative_to_absolute_urls

Converts any relative URls to absolute, fully qualified HTTP(s) URLs for the given
DOM Elements.

```php
public convert_relative_to_absolute_urls(\DOMNodeList<\DOMElement> $elements, string $attribute, string $url): void
```

**Parameters:**

| Parameter    | Type                          | Description                               |
|--------------|-------------------------------|-------------------------------------------|
| `$elements`  | **\DOMNodeList<\DOMElement>** | Elements.                                 |
| `$attribute` | **string**                    | HTML Attribute.                           |
| `$url`       | **string**                    | Absolute URL to prepend to relative URLs. |

***
### strip_html_head_body_tags

Strips <html>, <head> and <body> opening and closing tags from the given markup,
as well as the Content-Type meta tag we might have added in get_html().

```php
public strip_html_head_body_tags(string $markup): string
```

**Parameters:**

| Parameter | Type       | Description  |
|-----------|------------|--------------|
| `$markup` | **string** | HTML Markup. |

**Return Value:**

HTML Markup

***
### build_total_count_and_pagination_params

Adds total count and pagination parameters to the given array of existing API parameters.

```php
private build_total_count_and_pagination_params(array<string,string|int|bool> $params = [], bool $include_total_count = false, string $after_cursor = '', string $before_cursor = '', int $per_page = 100): array<string,string|int|bool>
```

**Parameters:**

| Parameter              | Type                                | Description                                        |
|------------------------|-------------------------------------|----------------------------------------------------|
| `$params`              | **array<string,string\|int\|bool>** | API parameters.                                    |
| `$include_total_count` | **bool**                            | Return total count of records.                     |
| `$after_cursor`        | **string**                          | Return results after the given pagination cursor.  |
| `$before_cursor`       | **string**                          | Return results before the given pagination cursor. |
| `$per_page`            | **int**                             | Number of results to return.                       |

***
### get

Performs a GET request to the API.

```php
public get(string $endpoint, array<string,int|string|bool|array<string,int|string>> $args = []): false|mixed
```

**Parameters:**

| Parameter   | Type                                                           | Description        |
|-------------|----------------------------------------------------------------|--------------------|
| `$endpoint` | **string**                                                     | API Endpoint.      |
| `$args`     | **array<string,int\|string\|bool\|array<string,int\|string>>** | Request arguments. |

***
### post

Performs a POST request to the API.

```php
public post(string $endpoint, array<string,bool|int|float|string|null|array<int|string,float|int|string|(string)[]>> $args = []): false|mixed
```

**Parameters:**

| Parameter   | Type                                                                                                | Description        |
|-------------|-----------------------------------------------------------------------------------------------------|--------------------|
| `$endpoint` | **string**                                                                                          | API Endpoint.      |
| `$args`     | **array<string,bool\|int\|float\|string\|null\|array<int\|string,float\|int\|string\|(string)[]>>** | Request arguments. |

***
### put

Performs a PUT request to the API.

```php
public put(string $endpoint, array<string,bool|int|string|array<string,int|string>> $args = []): false|mixed
```

**Parameters:**

| Parameter   | Type                                                           | Description        |
|-------------|----------------------------------------------------------------|--------------------|
| `$endpoint` | **string**                                                     | API Endpoint.      |
| `$args`     | **array<string,bool\|int\|string\|array<string,int\|string>>** | Request arguments. |

***
### delete

Performs a DELETE request to the API.

```php
public delete(string $endpoint, array<string,int|string|array<string,int|string>> $args = []): false|mixed
```

**Parameters:**

| Parameter   | Type                                                     | Description        |
|-------------|----------------------------------------------------------|--------------------|
| `$endpoint` | **string**                                               | API Endpoint.      |
| `$args`     | **array<string,int\|string\|array<string,int\|string>>** | Request arguments. |

***
### request

Performs an API request.

```php
public request(string $endpoint, string $method, array<string,bool|int|float|string|null|array<int|string,float|int|string|(string)[]>> $args = []): false|mixed
```

* This method is **abstract**.
**Parameters:**

| Parameter   | Type                                                                                                | Description        |
|-------------|-----------------------------------------------------------------------------------------------------|--------------------|
| `$endpoint` | **string**                                                                                          | API Endpoint.      |
| `$method`   | **string**                                                                                          | Request method.    |
| `$args`     | **array<string,bool\|int\|float\|string\|null\|array<int\|string,float\|int\|string\|(string)[]>>** | Request arguments. |

**Throws:**

If JSON encoding arguments failed.
- [`Exception`](../Exception)

***
### get_request_headers

Returns the headers to use in an API request.

```php
public get_request_headers(string $type = 'application/json', bool $auth = true): array<string,string>
```

* This method is **abstract**.
**Parameters:**

| Parameter | Type       | Description                      |
|-----------|------------|----------------------------------|
| `$type`   | **string** | Accept and Content-Type Headers. |
| `$auth`   | **bool**   | Include authorization header.    |

***
### get_timeout

Returns the maximum amount of time to wait for
a response to the request before exiting.

```php
public get_timeout(): int
```

* This method is **abstract**.
**Return Value:**

Timeout, in seconds.

***
### get_user_agent

Returns the user agent string to use in all HTTP requests.

```php
public get_user_agent(): string
```

* This method is **abstract**.
***
