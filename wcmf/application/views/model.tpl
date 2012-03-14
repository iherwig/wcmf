{header value="Content-Type: text/javascript"}

{foreach $typeTemplates as $tpl}
{$type=$tpl->getType()}
dojo.provide("wcmf.model.{$type}Class");
{/foreach}

dojo.require("wcmf.model.meta.Node");
dojo.require("wcmf.model.meta.Model");

/**
 * This file contains definitions of all known model entities
 */

{foreach $typeTemplates as $tpl}
  {$type=$tpl->getType()}
  {$mapper=$tpl->getMapper()}
/**
 * Definition of model class {$type}
 */
dojo.declare("wcmf.model.{$type}Class", wcmf.model.meta.Node, {
  name: '{$type}',
  isRootType: {if $tpl->getProperty('isRootType') == true}true{else}false{/if},
{$orderBy=$mapper->getDefaultOrder()}
  sortInfo: {
    attribute: "{$orderBy.sortFieldName}",
    descending: {if $orderBy.sortDirection == "DESC"}true{else}false{/if},
    isSortkey: {if $orderBy.isSortkey}true{else}false{/if}

  },
  relationSortInfo: {
{foreach $mapper->getRelations() as $relation}
  {$orderBy=$mapper->getDefaultOrder($relation->getOtherRole())}
  {if $orderBy}
    {$relation->getOtherRole()}: {
      attribute: "{$orderBy.sortFieldName}",
      descending: {if $orderBy.sortDirection == "DESC"}true{else}false{/if},
      isSortkey: {if $orderBy.isSortkey}true{else}false{/if}

    }{if !$relation@last},
    {/if}
  {/if}
{/foreach}

  },
  attributes: [
{foreach $mapper->getAttributes() as $attribute}
    {
      name: "{$attribute->getName()}",
      type: "{$attribute->getType()}",
      isEditable: {if $attribute->getIsEditable()}true{else}false{/if},
      tags: [{if sizeof($attribute->getTags()) > 0}"{join('","',$attribute->getTags())}"{/if}]
    }{if !$attribute@last},
{/if}
{/foreach}

  ],
  relations: [
{foreach $mapper->getRelations() as $relation}
    {
      name: "{$relation->getOtherRole()}",
      type: "{$relation->getOtherType()}",
      maxMultiplicity: "{$relation->getOtherMaxMultiplicity()}",
      aggregrationKind: "{$relation->getOtherAggregationKind()}",
      navigability: "{$relation->getOtherNavigability()}",
      thisEndName: "{$relation->getThisRole()}"
    }{if !$relation@last},
{/if}
{/foreach}

  ],
  displayValues: [
{foreach $tpl->getDisplayValueNames() as $value}
    '{$value}'{if !$value@last},
{/if}
{/foreach}
  ]
});
// create a type instance
wcmf.model.{$type} = new wcmf.model.{$type}Class;
// register the type at the meta model
wcmf.model.meta.Model.registerType(wcmf.model.{$type});

{/foreach}
