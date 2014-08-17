# wCMF # {#mainpage}

<blockquote>
  A framework for building <em class="text-primary">reliable</em>,
  <em class="text-primary">maintainable</em> and
  <em class="text-primary">extendable</em> web applications in PHP
</blockquote>

wCMF's framework approach follows the philosophy, that the earlier developers
deal with the underlying infrastructure of an application the easier adjustments to
the specific needs of a project can be implemented, which - when using a out-of-the-box solutions -
would require programming work anyway. wCMF's clear object oriented design is based
on well known design patterns that allow for a quick introduction to the application's
architecture.

### Model Driven Development ###

To make application development even easier, wCMF features a model driven development
approach.

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
