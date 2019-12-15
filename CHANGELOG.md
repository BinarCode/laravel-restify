# Changelog

All notable changes to `laravel-restify` will be documented in this file

## [1.0.1] 2019-12-15

### Added
- RestController - abstract controller to be extended by your API controllers
- RestifyHandler - a global exception handler which transforms many types of generic exceptions into a standard repose with appropriate code for a consistent API 
- RestService - The main service which can be extended  considering you want to use repository/service architecture
- RestRepository - If using a repository/service architecture - all repositories have to extend this repository
- RestRepositoryInterface - If using a repository/service architecture - all repository interfaces have to extend this repository interface
- AuthService - full support for JWT authentication based on Laravel Passport client token
- Passport installation checker command: `restify:check-passport`
- Passportable - contract used for implementation by the authenticated entity
- A bunch of generic exceptions which may be used into your project
- Integration tests
