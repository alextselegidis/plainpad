# API

## Introduction 

Plainpad exposes a flexible REST API that will enables you to handle all the information of your installations through 
HTTP requests. The API is using JSON as its data transaction format and features many best practices in order to make 
the resources easily consumable. 

## Making Requests

The API supports Token based authentication which means that you will have to send the bearer token authentication 
header with every request you make. This way you can ensure that no passwords will be stolen during the requests. 
The API expects the username and password of an admin user. 

You can get a new token by creating a new session by sending the user credentials as the payload. This is the actual 
request that takes place while logging the user in. After a successful session creation you will receive a token that 
can be used for every other request.  

Header: 

`Athorization: Bearer {SESSION-TOKEN-HERE}`


The API honors the REST architecture which means that the client can use various HTTP verbs in order to perform various 
operations on the available resources. For example you should use a GET request for fetching resources, a POST for 
creating new and PUT for updating existing ones in the database. Finally a DELETE request will remove a resource from 
the system. 

GET requests accept some parameter helpers that enable the sort, search, pagination and minification of the responses 
information. Take a look in the following examples: 

### Search

Provide the `filter` parameter to perform a search in the resource.

```
https://url/to/public/api.php/v1/notes?filter=keyword
```

### Sort 

Sort the results in ascending or descending direction by providing the the respective sign and the property 
name to be used for sorting. 

```
https://url/to/public/api.php/v1/notes?sort=id&direction=asc
```

You can provide up to three sorting fields which will be applied in the provided order. 

### Paginate

Paginate the result by providing the `page` parameter along with the optional `length` parameter that defaults to 20. 

```
https://url/to/public/api.php/v1/notes?page=1&length=10
```

### Minimize

If you need to get only specific values from each JSON resource provide the `fields` GET parameter with a list of the 
required property names. 

```
https://url/to/public/api.php/v1/notes?fields=id,book,hash,notes
```

### Expected Responses
 
Most of the times the API will return the complete requested data in a JSON string but there are some cases that the 
responses might not contain a message but always a proper HTTP status code. 

## Resources & URIs

### Application 

#### Retrieve 

Returns information about the application and optionally an array of available updates.

**Authorization**

Not required

**Request**

- `GET /v1`

**Payload**

Not required

**Response**

```json
{
    "updates": []
}
```

#### Install

This will execute the database migrations if they were not executed yet.   

**Authorization**

Required 

**URL**

- `POST /v1`

**Payload**

(Empty)

**Response**

200 OK

#### Update

This will install the latest updates if available.    

**Authorization**

Required 

**URL**

- `PUT /v1`

**Payload**

(Empty)

**Response**

200 OK



### Sessions

#### Create 

Create a new session for the selected user. 

**Authorization**

Not required 

**URL**

- `POST /v1/sessions`

**Payload**

```json
{
    "email": "info@example.org",
    "password": "12345"
}
```

**Response**

201 Created

```json
{
    "user_id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
    "expires_at": "2020-03-23 04:38:09",
    "token": "dFt3PPUho4B2hPfmbrjDjkSbtQGLpAFrJptfgKPJBXc2fPSVyWMAuFCOISnS"
}
```

#### Delete 

Create a new session for the selected user. 

**Authorization**

Not required 

**URL**

- `DELETE /v1/sessions/{TOKEN}`

**Payload**

Not required

**Response**

200 OK


### Notes

#### List

List all user notes. 

**Authorization**

Required 

**URL**

- `GET /v1/notes`

**Payload**

Not required

**Response**

200 OK

```json
[
  {
        "id": "644d6d5f-6c42-4c28-b9c1-d25a14cd7903",
        "user_id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
        "content": "Content",
        "title": "Title",
        "pinned": false,
        "created_at": "2020-03-20 09:34:02",
        "updated_at": "2020-03-20 09:34:08"
    }
]
```


#### Retrieve

Retrieve a note resource. 

**Authorization**

Required 

**URL**

- `GET /v1/notes/{ID}`

**Payload**

Not required

**Response**

200 OK

```json
{
    "id": "644d6d5f-6c42-4c28-b9c1-d25a14cd7903",
    "user_id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
    "content": "Content",
    "title": "Title",
    "pinned": false,
    "created_at": "2020-03-20 09:34:02",
    "updated_at": "2020-03-20 09:34:08"
}
```

#### Create

Create a new note for the selected user. 

**Authorization**

Required

**URL**

- `POST /v1/notes`

**Payload**

```json
{
    "id": "9ab18f31-ec8f-4022-a972-d66198ede704",
    "user_id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
    "title": "New Note",
    "content": "This is the new note content.",
    "pinned": false,
    "updated_at": "2020-03-22 16:42:25",
    "created_at": "2020-03-22 16:42:25"
}
```

**Response**

201 OK

```json
{
    "id": "72e27310-c9d7-4584-b887-846beb8331d7",
    "user_id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
    "title": "New Note",
    "content": "This is the new note content.",
    "pinned": false,
    "updated_at": "2020-03-22 16:44:58",
    "created_at": "2020-03-22 16:44:58"
}
```

#### Update

Update an existing note for the selected user. 

**Authorization**

Required

**URL**

- `POST /v1/notes/{ID}`

**Payload**

```json
{
    "title": "Updated Note",
    "content": "This is the updated note content.",
    "pinned": false
}
```

**Response**

201 OK

```json
{
    "id": "72e27310-c9d7-4584-b887-846beb8331d7",
    "user_id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
    "title": "Updated Note",
    "content": "This is the updated note content.",
    "pinned": false,
    "updated_at": "2020-03-22 16:44:58",
    "created_at": "2020-03-22 16:44:58"
}
```

#### Delete

Delete an existing note for the selected user. 

**Authorization**

Required

**URL**

- `DELETE /v1/notes/{ID}`

**Payload**

Not Required
 
**Response**

200 OK


### Users

#### List

List all user users. 

**Authorization**

Required 

**URL**

- `GET /v1/users`

**Payload**

Not required

**Response**

200 OK

```json
[
  {
      "id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
      "name": "John Doe",
      "email": "admin@example.org",
      "admin": true,
      "locale": "en-US",
      "view": "compact",
      "line": "full",
      "sort": "modified",
      "theme": "light",
      "encrypt": false,
      "created_at": "2020-03-12 20:07:08",
      "updated_at": "2020-03-22 15:51:24"
  }
]
```


#### Retrieve

Retrieve a note resource. 

**Authorization**

Required 

**URL**

- `GET /v1/users/{ID}`

**Payload**

Not required

**Response**

200 OK

```json
{
    "id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
    "name": "John Doe",
    "email": "admin@example.org",
    "admin": true,
    "locale": "en-US",
    "view": "compact",
    "line": "full",
    "sort": "modified",
    "theme": "light",
    "encrypt": false,
    "created_at": "2020-03-12 20:07:08",
    "updated_at": "2020-03-22 15:51:24"
}
```

#### Create

Create a new note for the selected user. 

**Authorization**

Required

**URL**

- `POST /v1/users`

**Payload**

```json
{
	"name": "John Doe",
	"email": "user@exmaple.org",
	"password": "12345",
	"locale": "en-US",
	"view": "compact", 
	"line": "full",
	"sort": "modified",
	"theme": "light",
	"admin": false
}
```

**Response**

201 OK

```json
{
    "id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
    "name": "John Doe",
    "email": "admin@example.org",
    "admin": true,
    "locale": "en-US",
    "view": "compact",
    "line": "full",
    "sort": "modified",
    "theme": "light",
    "encrypt": false,
    "created_at": "2020-03-12 20:07:08",
    "updated_at": "2020-03-22 15:51:24"
}
```

#### Update

Update an existing note for the selected user. 

**Authorization**

Required

**URL**

- `POST /v1/users/{ID}`

**Payload**

```json
{
	"name": "John Doe",
	"email": "user@exmaple.org",
	"password": "12345",
	"locale": "en-US",
	"view": "compact", 
	"line": "full",
	"sort": "modified",
	"theme": "light",
	"admin": false
}
```

**Response**

200 OK

```json
{
    "id": "e150a31c-5987-41d7-bd16-b42d87dd3bce",
    "name": "John Doe",
    "email": "admin@example.org",
    "admin": true,
    "locale": "en-US",
    "view": "compact",
    "line": "full",
    "sort": "modified",
    "theme": "light",
    "encrypt": false,
    "created_at": "2020-03-12 20:07:08",
    "updated_at": "2020-03-22 15:51:24"
}
```

#### Delete

Delete an existing note for the selected user. 

**Authorization**

Required

**URL**

- `DELETE /v1/users/{ID}`

**Payload**

Not Required
 
**Response**

200 OK

#### Recovery

Recover the password of an existing user (email is sent).  

**Authorization**

Required

**URL**

- `POST /v1/users/recover`

**Payload**

```json
{
  "email": "user@example.org"
}
```
 
**Response**

200 OK

### Settings 

#### Update 

Update the application settings. 

**Authorization**

Required (only for admins)

**URL**

- `PUT /v1/settings`

**Payload**

```json
{
    "default_locale": "de-DE",
    "mail_driver": "smtp",
    "mail_host": "smtp.mailtrap.io",
    "mail_port": "2525",
    "mail_username": "",
    "mail_password": "",
    "mail_encryption": "tls",
    "mail_from_address": "hello@example.org",
    "mail_from_name": "Plainpad"
}
```
 
**Response**

200 OK

#### Retrieve 

Retrieve the application settings. 

**Authorization**

Required (only for admins)

**URL**

- `GET /v1/settings`

**Payload**

Not required
 
**Response**

200 OK

```json
{
    "default_locale": "de-DE",
    "mail_driver": "smtp",
    "mail_host": "smtp.mailtrap.io",
    "mail_port": "2525",
    "mail_username": "",
    "mail_password": "",
    "mail_encryption": "tls",
    "mail_from_address": "hello@example.org",
    "mail_from_name": "Plainpad"
}
```

[Back](readme.md)
 
