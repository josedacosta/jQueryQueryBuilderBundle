# from jQuery QueryBuilder to Doctrine QueryBuilder

# Status

Status Label  | Status Value
--------------|-------------
Build | [![Build Status](https://travis-ci.org/timgws/QueryBuilderParser.svg?branch=master)](https://travis-ci.org/timgws/QueryBuilderParser)
Insights | [![SensioLabsInsight](https://insight.sensiolabs.com/projects/70403e01-ad39-4117-bdef-d0c09c382555/mini.png?branch=master)](https://insight.sensiolabs.com/projects/70403e01-ad39-4117-bdef-d0c09c382555)
Code Climate | [![Code Climate](https://codeclimate.com/github/timgws/QueryBuilderParser/badges/gpa.svg)](https://codeclimate.com/github/timgws/QueryBuilderParser)
Test Coverage | [![Coverage Status](https://coveralls.io/repos/github/timgws/QueryBuilderParser/badge.svg?branch=master)](https://coveralls.io/github/timgws/QueryBuilderParser?branch=master)

# Introduction

This **Symfony Bundle** converts the *JSON* from the **jQuery QueryBuilder** into a **Doctrine QueryBuilder**, in fact, you must provide a "QueryBuilder Doctrine" in parameter, and the "where" conditions of the JSON are added, which is done on this "QueryBuilder Doctrine", and it is returned to you

jQuery QueryBuilder :<br>
http://querybuilder.js.org/<br>
http://querybuilder.js.org/demo.html

Doctrine QueryBuilder :<br>
http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/query-builder.html

**Logic**:

**Entrance :**
* Doctrine createQueryBuilder()* and *jQuery QueryBuilder JSON*

**Output :**
* Doctrine createQueryBuilder()*  with the WHERE conditions of the *jQuery QueryBuilder JSON*



# How to use it

To convert a JSON jQuery QueryBuilder to a QueryBuilder Doctrine :

    <?php
        echo "Hello world!";
    ?>


# Acknowledgments

Many thanks to [**Tim Groeneveld (timgws)**](https://github.com/timgws "Tim Groeneveld (timgws)") !!

Because a large majority of the code of this project is inspired (re-copies) of its project:
*QueryBuilderParser* of *Tim Groeneveld (timgws)*
https://github.com/timgws/QueryBuilderParser
And a more global thank you to all the contributors of the project *QueryBuilderParser* :
https://github.com/timgws/QueryBuilderParser/graphs/contributors


# Reporting Issues

If you do find an issue, please feel free to report it with [new issue](https://github.com/josedacosta/jQueryQueryBuilderBundle/issues) for this project.

Alternatively, fork the project and make a pull request :)
