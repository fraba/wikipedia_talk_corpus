Create corpus from Wikipedia talk pages
=================

The goal of this script is to create a corpus of informal online conversation to be applied to Natural Language Processing projects. Wikipedia offers a huge repertoire of conversation (although unstructured) on very different topic in the 'Talk' page of each article. Also contributions to the 'Talk' page are often signed by the author, then allowing for more sophisticated language analysis. 

The script parses the 'Talk' pages contained in a XML Wikipedia database dump (you find it here: http://en.wikipedia.org/wiki/Wikipedia:Database_download). The script attempts to identify the authors involved in the discussion. The script enters the parsed data into a SQLite database structured in the same fields: pageId, text, user, date.

The talk page is highly unstructured and the parsed data reflect it. The script is based on the Italian version of Wikipedia but can be easily adapted to be applied to different version.

This project is 'working in progress'. Contributions are welcomed.
