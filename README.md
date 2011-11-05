# Sphinx Plugin for CakePHP 1.3

A complete datasource to faciliate real-time CRUD with a Sphinx server via SphinxQL.

## Pre-Installation

### Sphinx Server

[Sphinx 2.0.1](http://sphinxsearch.com/downloads/) or greater is required for this plugin. Follow the instructions at the Sphinx website to install.

### Assumptions

This plugin assumes you have working knowledge of Sphinx and SphinxQL. There are many concepts/limitations that are [documented by Sphinx](http://sphinxsearch.com/docs/2.0.1/). It would be repetitive to document them here as well. Make sure you understand the quirks of SphinxQL; this plugin will, more or less, exhibit the same quirks.

## Installation

1. Extract the downloaded archive from [here](http://github.com/anthonyp/CakePHP-Sphinx-Plugin/zipball/master)
2. Move or copy the extracted directory anthonyp-CakePHP-Sphinx-Plugin-[hash] to /path/to/your/app/plugins/sphinx
3. Sphinx creates a database config for itself named `sphinx`. By default, it connects to port 9306 on localhost with no username or password. If you would like to change this behavior, simply override those settings in your `database.php` file using a config named `sphinx`.
4. Each Sphinx index corresponds to a model. In the Sphinx config file, set up a test suite index for yourself as follows:
	index sphinx_tests
	{
	type			= rt
	rt_mem_limit	= 512M

	path			= /path/to/data/sphinx_tests
	charset_type	= utf-8

	rt_field		= description
	rt_attr_string	= name
	rt_attr_uint	= foreign_id
	}
5. Set up any custom indexes you need for your app in a similar fashion (note that the index name is an underscored, pluralized version of your model name).

## Instructions / Usage

### Setting up Your Models

Each index should have a corresponding model, keeping the following in mind:

1. A Sphinx model file should include `SphinxAppModel`:
	`App::import('Model', 'Sphinx.SphinxAppModel');`
	It should then extend itself off of that:
	`class MySphinxModel extends SphinxAppModel {`
2. The `$_full_text_indexes' property should be defined. This is an array of fields that are Sphinx full-text indexes.
3. The `$_attributes` property should be defined. This is an array of fields that are Sphinx attributes.
4. The `$_schema` property should be defined. This is a standard CakePHP schema array and should contain the `id` field plus any other full-text indexes and/or attributes you are using.

An example model can be found in the test suite: `tests/cases/models/datasources/sphinx_source.test.php`

### CRUD Operations

All CRUD operations can be used as expected to interact with an index. For the most part, the only limitations are the ones SphinxQL places on us. [Read the SphinxQL documentation](http://sphinxsearch.com/docs/2.0.1/sphinxql-reference.html) for further details.

#### Full-Text Searching

Full-text searching can be accomplished using MATCH, like so:

		$this->SphinxExample->find('all', array(
			'conditions' => array(
				'MATCH' => "query here"
			)
		)));

#### Using SphinxQL Options

SphinxQL options can be used to change how the query is interpreted by Sphinx. Simply add the `options` key to your array, and list each option in key/value format. For example:

		$this->SphinxExample->find('all', array(
			'conditions' => array(
				'MATCH' => "query here"
			),
			'options' => array(
				'ranker' => 'proximity'
			)
		)));

## Known Issues

### AND/OR Operators

Although SphinxQL supports AND/OR operators, this plugin currently does not.

## Authors

See the AUTHORS file.

## Copyright & License

Sphinx Plugin for CakePHP is Copyright (c) 2011 Anthony Putignano. if not otherwise stated. The code is distributed under the terms of the MIT License. For the full license text see the LICENSE file.