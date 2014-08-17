# wCMF # {#mainpage}


@htmlonly
<div class="jumbotron">
  <p>A PHP framework for building <em>reliable</em>, <em>maintainable</em> and <em>extendable</em> web applications</p>
</div>

<div class="row">
  <div class="col-md-4"><h3><i class="fa fa-sitemap fa-2x pull-left"></i> Model</h3>
    Start by designing your application in an UML model. wCMF not only supports modeling of entities, controllers and views, but also of configuration and application flow.
  </div>
  <div class="col-md-4"><h3><i class="fa fa-gears fa-2x pull-left"></i> Build</h3>
    Generate persistence mappers, controllers, views and configuration files from the model. After that a default application based on the Dojo Toolkit is ready to be used.
  </div>
  <div class="col-md-4"><h3><i class="fa fa-expand fa-2x pull-left"></i> Extend</h3>
    wCMF's clear object oriented design is based on well known design patterns, that allow to extend the application in every aspect. Custom code is protected from subsequent generator runs.
  </div>
</div>
@endhtmlonly

### Features ###

- Full featured object persistence layer:
  - Flexible mapper architecture with adapter to RDBMS
  - Optimistic and pessimistic object locking
  - Searching using template based object query and criteria api
  - Query caching and eager relation loading
  - Transaction support
- Presentation layer based on the Smarty template engine
- Configuration of the application flow through config files
- Dependency injection support
- Simple event processing
- Role based permission management (for actions, types, instances, instance properties)
- Lucene search engine integration
- I18n support
- Flexible logging (log4php)
- SOAP and REST interface supporting CRUD operations on all objects
- Code generator for model driven development (OpenArchitectureWare)
- Also available:
  - Modern Dojo based [default application](https://github.com/iherwig/wcmf-default-app) for content management





- @ref architecture
- @ref extensionpoints
- @ref configuration
- @ref dbschema
- @ref howto
- @ref howtostart
- @ref credits
