# mpsimpleproductimporter
Prestashop module for product import from a CSV file
CSV File properties:

Mandatory columns:
- id = supplier reference
- reference = product reference
- name = name of the product
- new = 0/1
- category default = id of the category default
- category others = list of others categories separated by comma
- #FEAT <id:name> = Custom field: set a feature for this product, there can be more than one column
  -- id: id_feature
  -- name: name feature for current language
  -- content: <id:name> of the feature value
  -- example: #FEAT 130:Size with content of 1820:XXL indicates a the feature Size:XXL with ids 130:1820, if this feature doesn't exists, it will be created
- #ATTR <id:name> = Custom field: set an attribute for this product, there can be more than one column. it works like #FEAT, in the end, it will be crated all combinations with these attributes.
- #DESC <description> = Custom field: set the short description for this product, there can be more than one of these columns. <description> indicates the title and the content indicates the description.
  -- example: #DESC Buttons with content three, it will generate a description -Buttons: three
- #TAX = tax group, content is the id of the tax group
- WHOLESALE PRICE = wholesale price with no tax
- PRICE = Sell price with no tax

