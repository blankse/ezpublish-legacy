<form method="post" action={"content/action"|ezurl}> 

<h1>{$node.name}</h1>

{attribute_view_gui attribute=$node.object.data_map.image}

{attribute_view_gui attribute=$node.object.data_map.product_number}

{attribute_view_gui attribute=$node.object.data_map.description}

{attribute_view_gui attribute=$node.object.data_map.price}

{let related_objects=$node.object.related_contentobject_array}
    {section show=$related_objects} 
       <h2>Related products</h2>  
           {section name=ContentObject  loop=$related_objects show=$related_objects} 
              {content_view_gui view=text_linked content_object=$ContentObject:item}
           {/section}
    {/section}
{/let}

[todo-add-print-icon]
<a href={concat('/layout/set/print/', $node.url_alias )|ezurl}>Printer version</a>

<input type="submit" class="defaultbutton" name="ActionAddToBasket" value="Add to basket" />

<input type="hidden" name="ContentNodeID" value="{$node.node_id}" />
<input type="hidden" name="ContentObjectID" value="{$node.object.id}" />
<input type="hidden" name="ViewMode" value="full" /> 


<input class="button" type="submit" name="ActionAddToNotification" value="Notify me about updates to {$node.name}" />


<h3>Related purchases</h3>
{let related_purchase=fetch( shop, related_purchase, hash( contentobject_id, $node.contentobject_id,
                                                           limit, 10 ) )}
{section name=Products loop=$related_purchase}
<p>
  <a href={concat('content/view/full/',$Products:item.main_node_id)|ezurl}>{$Products:item.name|wash}</a>
</p>
{/section}
{/let}
</form>