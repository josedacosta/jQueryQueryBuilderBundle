<?php

namespace JD\jQueryQueryBuilderBundle\Services;

use Doctrine\ORM\QueryBuilder;
use \stdClass;

class jQueryQueryBuilderParser
{

    use jQueryQueryBuilderFunctions;

    protected $fields;

    /**
     * QueryBuilderParser's parse function!
     *
     * Build a query based on JSON that has been passed into the function, onto the QueryBuilder passed into the function.
     *
     * @param $json
     * @param QueryBuilder $queryBuilder
     *
     * @throws QBParseException
     *
     * @return QueryBuilder
     */
    public function jQueryToDoctrine(string $json, QueryBuilder $queryBuilder, array $fields = null)
    {
        $this->fields = $fields;

        $query = $this->decodeJSON($json);
        if (!isset($query->rules) || !is_array($query->rules)) {
            return $queryBuilder;
        }
        if (count($query->rules) < 1) {
            return $queryBuilder;
        }
        return $this->loopThroughRules($query->rules, $queryBuilder, $query->condition);
    }

    /**
     * Called by parse, loops through all the rules to find out if nested or not.
     *
     * @param array $rules
     * @param QueryBuilder $queryBuilder
     * @param string $queryCondition
     *
     * @throws QBParseException
     *
     * @return QueryBuilder
     */
    protected function loopThroughRules(array $rules, QueryBuilder $queryBuilder, $queryCondition = 'AND')
    {
        foreach ($rules as $rule) {

            // si makeQuery ne voit pas les champs corrects, il retournera $queryBuilder sans modifications
            $queryBuilder = $this->makeQuery($queryBuilder, $rule, $queryCondition);

            // si plusieurs groupes de rules
            if ($this->isNested($rule)) {
                $queryBuilder = $this->createNestedQuery($queryBuilder, $rule, $queryCondition);
            }

        }

        return $queryBuilder;
    }

    /**
     * makeQuery: The money maker!
     *
     * Take a particular rule and make build something that the QueryBuilder would be proud of.
     *
     * Make sure that all the correct fields are in the rule object then add the expression to
     * the query that was given by the user to the QueryBuilder.
     *
     * @param QueryBuilder $query
     * @param stdClass $rule
     * @param string $queryCondition and/or...
     *
     * @throws QBParseException
     *
     * @return QueryBuilder
     */
    protected function makeQuery(QueryBuilder $queryBuilder, stdClass $rule, $queryCondition = 'AND')
    {

        // vérifie que la $rule est correcte
        try {
            $value = $this->getValueForQueryFromRule($rule);
        } catch (\Exception $e) {
            return $queryBuilder;
        }

        return $this->convertIncomingQBtoQuery($queryBuilder, $rule, $value, $queryCondition);
    }

    /**
     * Ensure that the value is correct for the rule, try and set it if it's not.
     *
     * @param stdClass $rule
     *
     * @throws \Exception
     * @throws \timgws\QBParseException
     *
     * @return mixed
     */
    protected function getValueForQueryFromRule(stdClass $rule)
    {
        // assurez-vous que la plupart des champs communs de QueryBuilder ont été ajoutés
        $value = $this->getRuleValue($rule);

        // le "field" doit exister dans notre liste de "fields" (fournie à l'entrée)
        $this->ensureFieldIsAllowed($this->fields, $rule->field);

        // si l'opérateur SQL est défini pour ne pas avoir une valeur, assurez-vous que nous définissons la valeur à null
        if ($this->operators[$rule->operator]['accept_values'] === false) {
            return $this->operatorValueWhenNotAcceptingOne($rule);
        }

        // Convertissez l'opérateur (LIKE / NOT LIKE / GREATER THAN) qui nous est fourni par QueryBuilder
        // sur un que nous pouvons utiliser à l'intérieur de la requête SQL
        $sqlOperator = $this->operator_sql[$rule->operator];
        $operator = $sqlOperator['operator'];

        // vérifie que la valeur est un tableau uniquement si elle doit être
        $value = $this->getCorrectValue($operator, $rule, $value);

        return $value;
    }

    /**
     * Check if a given rule is correct.
     *
     * Just before making a query for a rule, we want to make sure that the field, operator and value are set
     *
     * @param stdClass $rule
     *
     * @return bool true if values are correct.
     */
    protected function checkRuleCorrect(stdClass $rule)
    {
        // vérifie la présence des valeurs indispensables
        if (!isset($rule->id, $rule->field, $rule->type, $rule->input, $rule->operator, $rule->value)) {
            return false;
        }
        // vérifie l'existance de l'opérateur
        if (!isset($this->operators[$rule->operator])) {
            return false;
        }
        return true;
    }

    /**
     * Give back the correct value when we don't accept one.
     *
     * @param $rule
     *
     * @return null|string
     */
    protected function operatorValueWhenNotAcceptingOne(stdClass $rule)
    {
        if ($rule->operator == 'is_empty' || $rule->operator == 'is_not_empty') {
            return '';
        }
        return null;
    }

    /**
     * Ensure that the value for a field is correct.
     *
     * Append/Prepend values for SQL statements, etc.
     *
     * @param $operator
     * @param stdClass $rule
     * @param $value
     *
     * @throws QBParseException
     *
     * @return string
     */
    protected function getCorrectValue($operator, stdClass $rule, $value)
    {
        $field = $rule->field;
        $sqlOperator = $this->operator_sql[$rule->operator];
        $requireArray = $this->operatorRequiresArray($operator);

        $value = $this->enforceArrayOrString($requireArray, $value, $field);

        return $this->appendOperatorIfRequired($requireArray, $value, $sqlOperator);
    }

    /**
     * Déterminer si une règle particulière est en réalité un groupe d'autres règles.
     *
     * @param $rule
     *
     * @return bool
     */
    protected function isNested($rule)
    {
        if (isset($rule->rules) && is_array($rule->rules) && count($rule->rules) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Convert an incomming rule from jQuery QueryBuilder to the Doctrine Querybuilder
     *
     * (This used to be part of makeQuery, where the name made sense, but I pulled it
     * out to reduce some duplicated code inside JoinSupportingQueryBuilder)
     *
     * @param QueryBuilder $query
     * @param stdClass $rule
     * @param mixed $value the value that needs to be queried in the database.
     * @param string $queryCondition and/or...
     * @return QueryBuilder
     */
    protected function convertIncomingQBtoQuery(QueryBuilder $queryBuilder, stdClass $rule, $value, $queryCondition = 'AND')
    {
        // Convertissez l'opérateur (LIKE / NOT LIKE / GREATER THAN) qui nous est fourni par QueryBuilder
        // sur un que nous pouvons utiliser à l'intérieur de la requête SQL
        $sqlOperator = $this->operator_sql[$rule->operator];
        $operator = $sqlOperator['operator'];
        $condition = strtolower($queryCondition);

        if ($this->operatorRequiresArray($operator)) {
            return $this->makeQueryWhenArray($queryBuilder, $rule, $sqlOperator, $value, $condition);
        } else if ($this->operatorIsNull($operator)) {
            return $this->makeQueryWhenNull($queryBuilder, $rule, $sqlOperator, $condition);
        }

        $key = uniqid('qb');
        // inutile de sécurisé "field" ici car il a été filtré à l'entrée, idem pour "operator"
        if ($condition === 'and') {
            return $queryBuilder->andWhere($rule->field . ' ' . $sqlOperator['operator'] . ' :' . $key)->setParameter($key, $value);
        } else if ($condition === 'or') {
            return $queryBuilder->orWhere($rule->field . ' ' . $sqlOperator['operator'] . ' :' . $key)->setParameter($key, $value);
        }

    }

    /**
     * Create nested queries
     *
     * When a rule is actually a group of rules, we want to build a nested query with the specified condition (AND/OR)
     *
     * @param QueryBuilder $queryBuilder
     * @param stdClass $rule
     * @param string|null $condition
     * @return QueryBuilder
     */
    protected function createNestedQuery(QueryBuilder $queryBuilder, stdClass $rule, $condition = null)
    {
        if ($condition === null) {
            $condition = $rule->condition;
        }

        $condition = $this->validateCondition($condition);

        // TODO à finir en Doctrine :
        exit('TODO : Doctrine Querybuilder where nested !!!');
        return $queryBuilder->whereNested(function ($query) use (&$rule, &$queryBuilder, &$condition) {
            foreach ($rule->rules as $loopRule) {
                $function = 'makeQuery';

                if ($this->isNested($loopRule)) {
                    $function = 'createNestedQuery';
                }

                $queryBuilder = $this->{$function}($query, $loopRule, $rule->condition);
            }

        }, $condition);
    }

}
