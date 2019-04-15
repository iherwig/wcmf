wCMF
----
wCMF (wemove Content Management Framework) is a lightweight
Model Driven Development approach to application
development based on the MVC pattern. It allows to create any kind of
PHP web application, e.g. CRM, CMS from an UML model.

[![Build Status](https://img.shields.io/travis/iherwig/wcmf/master.svg?style=flat-square)](https://travis-ci.org/iherwig/wcmf)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=iherwig_wcmf&metric=alert_status)](https://sonarcloud.io/dashboard?id=iherwig_wcmf) 
[![Packagist License](https://img.shields.io/packagist/l/wcmf/wcmf.svg?style=flat-square)](https://github.com/iherwig/wcmf/blob/master/LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/wcmf/wcmf.svg?style=flat-square)](https://packagist.org/packages/wcmf/wcmf)
[![Packagist Downloads](https://img.shields.io/packagist/dt/wcmf/wcmf.svg?style=flat-square)](https://packagist.org/packages/wcmf/wcmf)
<!--[![SonarCloud Coverage](https://sonarcloud.io/api/project_badges/measure?project=iherwig_wcmf&metric=coverage)](https://sonarcloud.io/component_measures/metric/coverage/list?id=iherwig_wcmf)-->
[![SonarCloud Bugs](https://sonarcloud.io/api/project_badges/measure?project=iherwig_wcmf&metric=bugs)](https://sonarcloud.io/component_measures/metric/reliability_rating/list?id=iherwig_wcmf)
[![SonarCloud Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=iherwig_wcmf&metric=vulnerabilities)](https://sonarcloud.io/component_measures/metric/security_rating/list?id=iherwig_wcmf)

Features
--------
- Full featured object [persistence layer](http://wcmf.wemove.com/persistence.html):
    - Flexible [mapper](http://wcmf.wemove.com/persistence.html#pers_mappers) architecture with adapter to RDBMS
    - Optimistic and pessimistic [object locking](http://wcmf.wemove.com/persistence.html#Concurrency)
    - [Searching](http://wcmf.wemove.com/persistence.html#pers_search) using template based object query and criteria api
    - Query caching and [eager relation loading](http://wcmf.wemove.com/persistence.html#pers_builddepth)
    - [Transaction](http://wcmf.wemove.com/persistence.html#pers_tx) support
- [Role based permission management](http://wcmf.wemove.com/security.html#Authorization) (for actions, types, instances, instance properties)
- [Event system](http://wcmf.wemove.com/presentation.html#Events)
- [Dependency injection](http://wcmf.wemove.com/configuration.html#Dependencyinjection) support
- Configuration based [routing](http://wcmf.wemove.com/presentation.html#pres_routing)
- [Smarty Template Engine](http://www.smarty.net/) integration
- [Lucene Search Engine](http://framework.zend.com/manual/1.12/en/zend.search.lucene.overview.html) integration
- I18n support
- Flexible logging ([Monolog](https://github.com/Seldaek/monolog), [log4php](https://logging.apache.org/log4php/))
- [SOAP and REST interfaces](http://wcmf.wemove.com/presentation.html#WebserviceAPIs) supporting CRUD operations on all objects
- [Eclipse MDT/UML2](http://wiki.eclipse.org/MDT-UML2) compatible [code generator](http://wcmf.wemove.com/model.html#Generator)
- Modern [Dojo](https://dojotoolkit.org/) based [default application](https://github.com/iherwig/wcmf-default-app) for content management

License
--------
wCMF is available under an open source license ([MIT License](https://github.com/iherwig/wcmf/blob/master/LICENSE)).
