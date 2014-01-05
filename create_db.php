//    Copyright 2014 Francesco Bailo
    
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.

//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.

//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.

<?php

try
{
  $dbhandle = new PDO('sqlite:talks.sqlite');
  $dbhandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
  $dbhandle->exec('CREATE TABLE page' .'(
              pageId INT PRIMARY KEY,
	      title TEXT,
              text TEXT,
              timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
              )');
  $dbhandle->exec('CREATE INDEX page_index ON page(pageId)');

  $dbhandle->exec('CREATE TABLE talk' .'(
              pageId INT PRIMARY KEY,
	      text TEXT NOT NULL,
              user TEXT,
              date DATETIME,
              timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY(pageId) REFERENCES page(pageId)
              )');
  $dbhandle->exec('CREATE INDEX talk_index ON talk(pageId)');
  

  $dbhandle = NULL;
}
catch(PDOException $e)
  {
  print 'Exception : '.$e->getMessage();
  }

?>