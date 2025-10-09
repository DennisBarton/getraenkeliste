<?php
$to_tex = 
'\documentclass[parskip=half]{scrlttr2}
\usepackage{graphicx}
\usepackage{ngerman}
\usepackage[utf8]{inputenc}
%\setkomavar{fromfax}{+49 1234 5567}
\setkomavar{fromphone}{+49 1234 5566}
\setkomavar{fromname}{Vorname Nachname}
\setkomavar{fromaddress}{Straße 1, 12345 Ort}
\setkomavar{fromemail}{email@server.de}
%\setkomavar{yourmail}{asdf}
%\setkomavar{yourref}{abcd2002/03/02-1}
\usepackage{color,calc,ngerman,mathptmx,tabularx}
\usepackage[gen]{eurosym}
\firsthead{\null\hfill
  \parbox[t][\headheight][t]{9cm}{%
    \vspace*{1cm}
    \raggedright\includegraphics[width=\linewidth]{./includes/Logo1_small.png}\\\\%[\baselineskip]
    }%
}
\makeatletter
\@setplength{firstfootvpos}{273mm}
\makeatother
\firstfoot{
  \textbf{Rechungsbetrag ohne Abzug zahlbar innerhalb von 7 Tagen ab Rechnungsdatum.}
  \newline
  \footnotesize\color[gray]{.5}%
  \hrule
  \parbox[t]{0.333\textwidth}{
    \usekomavar{fromname}\\\\
    Straße 1\\\\
    12345 Ort\\\
  }
  \parbox[t]{0.333\textwidth}{
    \usekomavar*{fromphone} \usekomavar{fromphone}\\\\
    \usekomavar*{fromemail} \usekomavar{fromemail}\\\\
    Ust-Id: DE 12345678
  }
  \parbox[t]{0.333\textwidth}{
    Bank\\\\
    IBAN: DE12 1234 1234 1234 1234 12\\\\
    BIC: AASSDDFF
  }
}




';
