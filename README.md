wCMF
----
wCMF (wemove Content Management Framework) is a lightweight 
Model Driven Development approach to application
development based on the MVC pattern. It allows to create any kind of
PHP web application, e.g. CRM, CMS from an UML model.

Features
--------
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
