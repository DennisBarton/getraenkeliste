<?php
$site_name = "Verkauf eintragen";
include ("./includes/header.php");
?>




<?php
  $produkte_query = $pdo->query (
      "SELECT produkt_id,name,preis,bestand FROM db_produkte_standard");
  $produkte = $produkte_query -> fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
  $personen_query = $pdo->query (
      "SELECT person_id,nachname,vorname FROM db_personen");
  $personen = $personen_query -> fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
?>
<div class="form" >  
  <form action="eintrag_speichern.php" method="post" name="verkauf_eintrag" onSubmit="return confirmVerkauf()">

    <input type="hidden" name="action" value="verkauf">
    <label for="Datum">Verkauf am:</label>
    <input type="date" name="Datum" id="Datum" value="<?=get_today()?>" >

    <label for="Produkt_ID">Produkt ID:</label>
    <select name="Produkt_ID" id="Produkt_ID" onclick="updateProduktinformationen()">
    <?php foreach ($produkte as $Produkt_ID=>$row) { ?>
      <option value="<?=$Produkt_ID?>">
        <?=$Produkt_ID?> <?=$row['name']?>
      </option>
    <?php } ?>
    </select>

    <label for="Menge">Menge:</label>
    <label for="Menge" id="verfuegbar">dummy</label>
    <input type="number" name="Menge" id="Menge" min="1" max="99999" required />

    <label for="Person_ID">Person:</label>
    <select name="Person_ID" id="Person_ID" onchange="handlePersonChange()">
      <?php foreach ($personen as $Person_ID => $row) { ?>
        <option value="<?=$Person_ID?>">
          <?=$Person_ID?> <?=$row['nachname']?>, <?=$row['vorname']?>
        </option>
      <?php } ?>
      <option value="__new__">➕ Neue Person hinzufügen</option>
    </select>

    <div id="neuePersonForm" style="display:none; margin-bottom: 1em;">
      <label for="neueVorname">Vorname:</label>
      <input type="text" id="neueVorname" name="neueVorname" />

      <label for="neueNachname">Nachname:</label>
      <input type="text" id="neueNachname" name="neueNachname" />

      <button type="button" onclick="neuePersonSpeichern()">Speichern</button>
    </div>

    <label for="Verkaufspreis">Verkaufspreis:</label>
    <input type="number" step="0.01" min="0" name="Verkaufspreis" id="Verkaufspreis" required />




    <input type="submit" />
</form>
</div> 



<script type="text/javascript">
  var produkte=<?php echo json_encode($produkte, JSON_PRETTY_PRINT)?>;
  var gewaehltesProdukt = document.getElementById("Produkt_ID");
  var kunden=<?php echo json_encode($personen, JSON_PRETTY_PRINT)?>;
  var gewaehlterKunde = document.getElementById("Person_ID");

  function confirmVerkauf() {
    let confirmMessage = "Wollen Sie diesen Verkauf eintragen:\n\n" +
      "Datum:          " + document.getElementById("Datum").value + "\n" +
      "Produkt:        " + gewaehltesProdukt.value + " " + produkte[gewaehltesProdukt.value].name + "\n" +
      "Menge:          " + document.getElementById("Menge").value + "\n" +
      "Kunde:          " + gewaehlterKunde.value + " " + kunden[gewaehlterKunde.value].nachname + ", " + kunden[gewaehlterKunde.value].vorname + "\n" +
      "Verkaufspreis:  " + document.getElementById("Verkaufspreis").value + " €\n";
    return confirm(confirmMessage);
  }



  function updateProduktinformationen() {
    var angezeigterPreis = document.getElementById("Verkaufspreis");


    angezeigterPreis.value=produkte[gewaehltesProdukt.value].preis;


    verfuegbar.innerHTML= "(Verfügbar: " + produkte[gewaehltesProdukt.value].bestand +
      ")";
    verfuegbar.style ="color:#808080";
  }
  updateProduktinformationen();

function handlePersonChange() {
    const selectedValue = document.getElementById("Person_ID").value;
    const formDiv = document.getElementById("neuePersonForm");
    
    if (selectedValue === "__new__") {
      formDiv.style.display = "block";
    } else {
      formDiv.style.display = "none";
    }
  }

  function neuePersonSpeichern() {
    const vorname = document.getElementById("neueVorname").value.trim();
    const nachname = document.getElementById("neueNachname").value.trim();

    if (!vorname || !nachname) {
      alert("Bitte Vorname und Nachname eingeben.");
      return;
    }

    fetch('neue_person_eintragen.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `vorname=${encodeURIComponent(vorname)}&nachname=${encodeURIComponent(nachname)}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const select = document.getElementById("Person_ID");

        // Create and insert new option
        const option = document.createElement("option");
        option.value = data.person_id;
        option.text = `${data.person_id} ${nachname}, ${vorname}`;
        option.selected = true;

        const lastOption = select.querySelector('option[value="__new__"]');
        select.insertBefore(option, lastOption);

        // Reset and hide form
        document.getElementById("neueVorname").value = "";
        document.getElementById("neueNachname").value = "";
        document.getElementById("neuePersonForm").style.display = "none";
      } else {
        alert("Fehler: " + data.message);
      }
    })
    .catch(error => {
      alert("Netzwerkfehler: " + error);
    });
  }

  // Initialize on page load
  window.addEventListener('DOMContentLoaded', handlePersonChange);

</script>


<?php
include ("./includes/footer.php");
?>
