# Adjacency To Nested set hierarchy converter for big data step by step in PHP (ATN)

ATN is a PHP-based Class for converting, updating, and fixing hierarchy data from the adjacency model to nested set model (large data set) step by step without affecting the running of the database during the conversion:
• ATN is optimized for large data and is using memory tables to steadily fix the table step by step
• For small data sets its possible to convert from the adjacency list model to nested set in a more straightforward manner (at once) but as large data sets require more time to convert it's best to avoid using those methods to prevent timeout errors and possible downtime
• The ATN.php is regularly used for updating the large scale data, over 5 million records, on a busy live tables
• For more information on hierarchy models visit: http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/

