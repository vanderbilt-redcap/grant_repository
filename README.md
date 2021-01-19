# grant_repository
Vanderbilt Grant Repository

Requires two REDCap projects, with project-ids configurable in base.php. Data dictionaries for two REDCap projects exist in the data_dictionary/ directory. This directory should be placed in REDCap's plugins/ directory. (We have not yet converted this to an External Module.)

README.docx is present only for historical purposes. It is not up-to-date and describes an older version of the system.

A composer.json file is included, and a small number of composer tools is required for the full operation.

In the uploaded fields, MS Word files, zips, and PDFs are supported. The downloadFile.php and download.php scripts will handle unpacking and converting them to rasterized PDFs.

Finally, a software license (MIT) is included as well.
