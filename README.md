wCMF
----
wCMF (wemove Content Management Framework) is a lightweight
Model Driven Development approach to application
development based on the MVC pattern. It allows to create any kind of
PHP web application, e.g. CRM, CMS from an UML model.

[![Build Status](https://img.shields.io/travis/iherwig/wcmf.svg?style=flat-square)](https://travis-ci.org/iherwig/wcmf)
[![GitHub license](https://img.shields.io/github/license/iherwig/wcmf.svg?style=flat-square)]()
[![Codacy Badge](https://img.shields.io/codacy/83131d82c278482a826b070f9736840e.svg?style=flat-square)](https://www.codacy.com/app/iherwig/wcmf)
[![Packagist Downloads](https://img.shields.io/packagist/dm/wcmf/wcmf.svg?style=flat-square)](https://packagist.org/packages/wcmf/wcmf)
<!--[![Packagist Version](https://img.shields.io/packagist/v/wcmf/wcmf.svg?style=flat-square)](https://packagist.org/packages/wcmf/wcmf)-->

Features
--------
- Full featured object persistence layer:
  - Flexible mapper architecture with adapter to RDBMS
  - Optimistic and pessimistic object locking
  - Searching using template based object query and criteria api
  - Query caching and eager relation loading
  - Transaction support
- Presentation layer based on the <a href="http://www.smarty.net/" target="_blank">Smarty Template Engine</a>
- Configuration of the application flow through config files
- Dependency injection support
- Basic event processing
- Role based permission management (for actions, types, instances, instance properties)
- <a href="http://framework.zend.com/manual/1.12/en/zend.search.lucene.overview.html" target="_blank">Lucene</a> search engine integration
- I18n support
- Flexible logging (<a href="https://github.com/Seldaek/monolog" target="_blank">Monolog</a>)
- SOAP and REST interface supporting CRUD operations on all objects
- Code generator for model-driven software development
- Available under an open source license (MIT)
- Modern [Dojo](https://dojotoolkit.org/) based [default application](https://github.com/iherwig/wcmf-default-app) for content management
