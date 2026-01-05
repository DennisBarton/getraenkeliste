<?php
// ============================================================
// queries.php
// Centralized SQL queries for Abrechnung page
// ============================================================

return [
    'entries' => "
        SELECT e.date, e.person, e.produkt, SUM(e.anzahl) AS sum, e.bezahlt, p.nachname, p.vorname
        FROM db_eintrag AS e
        JOIN db_personen AS p ON e.person = p.person_id
        WHERE bezahlt = :showPaid :dateClause
        GROUP BY e.date, e.person, e.produkt
        ORDER BY e.date DESC, p.nachname ASC, p.vorname ASC
    ",
    'persons' => "
        SELECT person_id, nachname, vorname FROM db_personen
        ORDER BY nachname ASC, vorname ASC
    ",
    'products' => "
        SELECT produkt_id, name, preis FROM db_produkte_standard
    "
];

