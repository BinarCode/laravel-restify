# Changelog

All notable changes to `laravel-restify` will be documented in this file

## [5.0.0] 2021-05-23
- Repositories CRUD + Bulk
- Actions
- Fields
- Search
...
  
## [1.0.0] 2019-12-23

### Added
- RestController - abstract controller to be extended by your API controllers
- RestifyHandler - a global exception handler which transforms many types of generic exceptions into a standard repose with appropriate code for a consistent API 
- AuthService - full support for JWT authentication based on Laravel Passport client token
- Passport installation checker command: `restify:check-passport`
- Passportable - contract used for implementation by the authenticated entity
- RestifySearchable - contract used for search support by the API (should be used along with InteractWithSearch)
- A bunch of generic exceptions which may be used into your project
- Unit and Integration tests
