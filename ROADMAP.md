## Roadmap

7.x 

### Fixes & Improvements

- [x] Clean up controllers
- [x] Reduce the main Repository class by using traits
- [x] Revisit the `InteractWithRepositories` trait and clean model queries accordingly
- [x] Clean up all tests using AssertableJson [x]
- [x] Make sure the `include` matches array key firstly, and secondly the relationship name
- [x] Improve performance for queries and relationships

### Features

- [x] Adding support for custom ActionLogs (ie ActionLog::register("project marked active by user Auth::id()", $project->id))
- [x] Ensure `$with` loads relationship in `show` requests
- [x] Make sure any action isn't permitted unless the Model Policy exists
- [x] Having a helper method that allow to return data using the repository from a custom controller `PostRepository::withModels(Post::query()->take(5)->get())->include('user')->serializeForShow()`
- [x] Serialize nested relationships
- [x] Ability to make an endpoint public using a policy method
- [x] Load specific fields for nested relationships
- [x] Load nested for relationships with a nested level higher than 2
- [x] Shorter definition of Related fields

8.x 

### Fixes

- [ ] Adding Larastan support
- [ ] Drop Psalm
- [ ] Adding PestPHP support
- [ ] Adding support for PHPStan and configure the level 4
- [ ] Request validations should be rewritten

### Features

- [x] Adding a command that lists all Restify registered routes `php artisan restify:routes`
- [ ] UI for Restify
- [x] Support for Laravel 10
- [x] Custom namespace and base directory for repositories
- [ ] Deprecate `show`  and use `view` as default policy method for `show` requests
- [ ] Deprecate `store`  and use `create` as default policy method for `store` requests so it's Laravel compatible

9.x

### Fixes & Improvements

- [ ] Complete Larastan integration and PHPStan Level 4 support
- [ ] Finalize PestPHP testing framework migration
- [ ] Request validation system overhaul
- [ ] Policy method standardization (`view` instead of `show`, `create` instead of `store`)
- [ ] Performance optimization for large datasets
- [ ] Memory usage optimization for bulk operations

10.0.0 - MCP

### Features

- [x] Model Context Protocol (MCP) integration for AI agents
- [x] Token-optimized field serialization for AI contexts
- [x] Advanced MCP server implementation with authentication
- [ ] Complete UI for Restify (web-based API management interface)
- [ ] Enhanced action logging with detailed audit trails
- [ ] Advanced query caching with intelligent invalidation
- [ ] Support for Laravel 12.x

10.0.0 - The Breakthrough Release

## Vision
Laravel Restify 10.0.0 represents a paradigm shift, positioning itself as the definitive Laravel API framework with enterprise-grade features, cutting-edge developer experience, and industry-first innovations.

### 🚀 Tier 1: Core Differentiators

#### **Auto-Generated GraphQL Schema & Resolvers** ✅
- [x] Dual REST/GraphQL API support from existing repositories
- [x] Automatic schema generation with `php artisan restify:graphql:generate`
- [x] GraphQL type mapping from Restify fields
- [x] Resolver class generation for CRUD operations
- [x] Authentication mocking for console context
- [x] Preview mode with detailed file generation overview
- [x] Lighthouse GraphQL integration support
- [ ] Built-in GraphQL subscriptions for real-time data (planned for future)
- **Impact**: Only Laravel package offering unified REST/GraphQL APIs

#### **TypeScript SDK Auto-Generation**  
- Auto-generated TypeScript client from API routes
- Full type safety with IntelliSense support
- React/Vue/Angular integration helpers
- Automatic API client updates on schema changes
- **Impact**: Best-in-class developer experience

#### **Real-Time Broadcasting Integration**
- WebSocket support for live API updates
- Server-Sent Events for real-time notifications  
- Built-in broadcasting for CRUD operations
- Laravel Echo integration out of the box
- **Impact**: Enables collaborative and live applications

### 🏢 Tier 2: Enterprise Features

#### **Built-in Multi-Tenancy & Team Management**
- Native tenant isolation with automatic scoping
- Organization and team management APIs
- Hierarchical permission systems
- Tenant-aware caching and performance optimization
- **Impact**: Enterprise SaaS applications ready out of the box

#### **Advanced Monitoring & Observability**
- Request tracing and performance metrics
- Health check endpoints with custom monitors  
- Prometheus/Grafana integration
- Query performance insights and slow query detection
- Rate limiting with detailed analytics
- **Impact**: Production-ready monitoring and optimization

#### **Visual API Designer & Documentation**
- Interactive schema designer with drag-drop interface
- Auto-generated OpenAPI 3.0 specifications
- Live API playground with authentication
- Automated testing suite generation
- **Impact**: 60% faster API development and documentation

### 🤖 Tier 3: AI & Modern Features

#### **Enhanced MCP Capabilities** (Building on existing foundation)
- AI-powered query optimization suggestions
- Natural language to API query translation
- Automated test case generation from AI analysis
- Smart field recommendations based on usage patterns
- **Impact**: Most AI-friendly API framework in the market

#### **Advanced Caching & Performance**
- Redis-based intelligent response caching
- Query result caching with dependency-based invalidation
- CDN integration for file fields and static content
- Background job integration for heavy operations
- Database query optimization with automated indexing suggestions
- **Impact**: 10x performance improvement for complex APIs

#### **Developer Experience Revolution**
- Hot-reload API development with instant updates
- Visual debugging tools for API requests/responses
- Automated API testing with AI-generated test scenarios
- One-command deployment with Docker containers
- **Impact**: Fastest API development cycle in Laravel ecosystem

## 🎯 Market Positioning

With Laravel Restify 10.0.0, we establish market leadership by becoming:

- **The Only Unified Solution**: First Laravel package offering both REST and GraphQL APIs
- **Most Developer-Friendly**: Auto-generated TypeScript SDKs and visual tools  
- **Enterprise-Ready**: Built-in multi-tenancy, monitoring, and scalability
- **AI-First Architecture**: Most advanced AI integration in API frameworks
- **Performance Leader**: Advanced caching and optimization built-in

## 📈 Success Metrics

- **Developer Adoption**: 50k+ monthly downloads (current: ~15k)
- **Enterprise Clients**: 100+ companies using multi-tenancy features
- **Community Growth**: 5k+ GitHub stars (current: ~2k)
- **Documentation Quality**: 95%+ developer satisfaction scores
- **Performance Benchmarks**: 10x improvement in response times

## 🗓 Implementation Timeline

### Phase 1 (Months 1-3): Foundation Revolution
1. GraphQL Schema Generation
2. TypeScript SDK Auto-Generation  
3. Real-Time Broadcasting

### Phase 2 (Months 4-6): Enterprise Transformation
1. Multi-Tenancy System
2. Advanced Monitoring & Observability
3. Visual API Designer

### Phase 3 (Months 7-8): AI & Performance Excellence
1. Enhanced MCP Features
2. Advanced Caching & Performance
3. Developer Experience Tools

