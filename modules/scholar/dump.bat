@echo off

set db=%1
mysqldump -u root --no-create-info --order-by-primary --complete-insert --skip-extended-insert %db% scholar_events scholar_attachments scholar_files scholar_authors scholar_nodes scholar_people scholar_generic_suppinfo scholar_generics scholar_category_names scholar_categories
