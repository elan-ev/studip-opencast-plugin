# Codeception Tests

To run the api tests, one needs to have Stud.IP installation at hand with currently 3 users accounts with the following credentials:

| Username         | Password         |
| ---------------- | ---------------- |
| apitester        | apitester        |
| apitester_autor1 | apitester_autor1 |
| apitester_autor2 | apitester_autor2 |

Furthermore the URL of the Stud.IP installation needs to be configured in de `codeception.yml`

Make sure that the composer and npm packages are up to date with the composer.json and package.json.

To run the tests call `npm run tests`.

Under `docs` is the db scheme and the REST-API-definition