const xlsx = require("xlsx");

// Parser un fichier Excel ou CSV
exports.parseFile = async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: "Aucun fichier fourni" });
    }

    const fileName = req.file.originalname.toLowerCase();
    const isExcel = fileName.endsWith(".xlsx") || fileName.endsWith(".xls");
    const isCSV = fileName.endsWith(".csv");

    if (!isExcel && !isCSV) {
      return res.status(400).json({
        error: "Format non supporté. Utilisez .xlsx, .xls ou .csv",
      });
    }

    let data = [];
    let headers = [];
    let detectedClass = "";

    if (isExcel) {
      // Parser Excel
      const workbook = xlsx.read(req.file.buffer, { type: "buffer" });
      const sheetName = workbook.SheetNames[0];
      const sheet = workbook.Sheets[sheetName];

      // Convertir en JSON, en sautant les lignes vides de titre/infos
      const jsonData = xlsx.utils.sheet_to_json(sheet, { header: 1 });

      // Trouver où commencent les vrais headers
      // Généralement après les lignes de titre/info
      let headerRowIndex = 0;

      for (let i = 0; i < jsonData.length; i++) {
        const row = jsonData[i];
        // Chercher une ligne avec des en-têtes clairs
        const joined = row.join("").toLowerCase();

        // Détecter la classe (ex: "3B", "4A") depuis les premières lignes
        if (i < 3 && !detectedClass) {
          const firstCell = row[0] ? row[0].toString().trim() : "";
          // Chercher un pattern comme "3B" ou "3B 3B"
          const classMatch = firstCell.match(/^([0-9][A-Z]+)(\s+\1)?$/);
          if (classMatch) {
            detectedClass = classMatch[1];
          }
        }

        if (
          joined.includes("nom") ||
          joined.includes("prénom") ||
          joined.includes("prenom") ||
          joined.includes("name") ||
          joined.includes("classe") ||
          joined.includes("class")
        ) {
          headerRowIndex = i;
          break;
        }
      }

      // Extraire les headers
      headers = jsonData[headerRowIndex];

      // Construire les données
      for (let i = headerRowIndex + 1; i < jsonData.length; i++) {
        const row = jsonData[i];

        // Ignorer les lignes vides
        if (
          !row ||
          row.every(
            (cell) => cell === null || cell === undefined || cell === "",
          )
        ) {
          continue;
        }

        const obj = {};
        headers.forEach((header, idx) => {
          if (header !== null && header !== undefined && header !== "") {
            obj[header] = row[idx] || "";
          }
        });

        if (Object.values(obj).some((v) => v && v.toString().trim())) {
          data.push(obj);
        }
      }
    } else {
      // Parser CSV
      const text = req.file.buffer.toString("utf-8");
      const lines = text
        .trim()
        .split("\n")
        .filter((l) => l.trim());

      if (lines.length < 2) {
        return res.status(400).json({
          error: "Minimum 2 lignes requises (headers + data)",
        });
      }

      // Parser les headers (gérer les guillemets et virgules)
      headers = parseCSVLine(lines[0]);

      // Parser les données
      for (let i = 1; i < lines.length; i++) {
        const values = parseCSVLine(lines[i]);
        const row = {};
        headers.forEach((h, idx) => {
          row[h] = values[idx] || "";
        });

        if (Object.values(row).some((v) => v && v.toString().trim())) {
          data.push(row);
        }
      }
    }

    if (data.length === 0) {
      return res.status(400).json({
        error: "Aucune donnée valide trouvée dans le fichier",
      });
    }

    // Mapper les colonnes aux champs attendus
    const mappedData = mapColumns(data, headers, detectedClass);

    // Filtrer les enregistrements vides (sans prénom ET sans nom)
    const filteredData = mappedData.filter((record) => {
      const hasFirstName =
        record.firstName && record.firstName.trim().length > 0;
      const hasLastName = record.lastName && record.lastName.trim().length > 0;
      return hasFirstName || hasLastName;
    });

    if (filteredData.length === 0) {
      return res.status(400).json({
        error: "Aucun élève valide trouvé dans le fichier",
      });
    }

    res.json({
      ok: true,
      rawHeaders: headers,
      data: filteredData,
      count: filteredData.length,
      detectedClass: detectedClass,
    });
  } catch (error) {
    console.error("Error parsing file:", error);
    res.status(500).json({
      error: "Erreur lors du traitement du fichier: " + error.message,
    });
  }
};

// Parser une ligne CSV en gérant les guillemets
function parseCSVLine(line) {
  const result = [];
  let current = "";
  let insideQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const nextChar = line[i + 1];

    if (char === '"') {
      if (insideQuotes && nextChar === '"') {
        // Double quote = quote échappée
        current += '"';
        i++;
      } else {
        // Toggle quote state
        insideQuotes = !insideQuotes;
      }
    } else if (char === "," && !insideQuotes) {
      result.push(current.trim());
      current = "";
    } else {
      current += char;
    }
  }

  result.push(current.trim());
  return result;
}

// Mapper les colonnes du fichier aux champs attendus
function mapColumns(data, headers, defaultClass = "") {
  return data.map((row) => {
    const mapped = {
      firstName: "",
      lastName: "",
      className: defaultClass, // Utiliser la classe détectée par défaut
      gender: "",
      age: 0,
      dateOfBirth: "",
      formation: "",
      options: [],
    };

    // Mapper chaque colonne
    headers.forEach((header) => {
      if (!header || !row.hasOwnProperty(header)) return;

      const value = row[header] ? row[header].toString().trim() : "";
      if (!value) return;

      const headerLower = header.toLowerCase();

      // Mapper NOM PRENOM (combiné)
      if (
        (headerLower.includes("nom") &&
          (headerLower.includes("prénom") || headerLower.includes("prenom"))) ||
        (headerLower.includes("name") && headerLower.includes("first"))
      ) {
        const parts = value.split(/\s+/);
        if (parts.length > 1) {
          mapped.lastName = parts[0];
          mapped.firstName = parts.slice(1).join(" ");
        } else {
          mapped.firstName = value;
        }
      }
      // Mapper PRÉNOM
      else if (
        headerLower.includes("prénom") ||
        headerLower.includes("prenom") ||
        headerLower.includes("firstname")
      ) {
        mapped.firstName = value;
      }
      // Mapper NOM
      else if (headerLower.includes("nom") && !headerLower.includes("classe")) {
        mapped.lastName = value;
      }
      // Mapper CLASSE
      else if (
        headerLower.includes("classe") ||
        headerLower.includes("class")
      ) {
        mapped.className = value;
      }
      // Mapper GENDER/SEXE
      else if (
        headerLower.includes("gender") ||
        headerLower.includes("sexe") ||
        headerLower === "s"
      ) {
        mapped.gender = value.substring(0, 1).toUpperCase();
      }
      // Mapper ÂGE/AGE
      else if (headerLower.includes("age") || headerLower.includes("âge")) {
        mapped.age = parseInt(value) || 0;
      }
      // Mapper DATE DE NAISSANCE
      else if (
        headerLower.includes("date") &&
        headerLower.includes("naissance")
      ) {
        mapped.dateOfBirth = value;
      }
      // Mapper FORMATION
      else if (headerLower.includes("formation")) {
        mapped.formation = value;
      }
      // Mapper OPTIONS (colonnes qui contiennent des codes d'option)
      else if (
        headerLower.includes("option") ||
        /^[A-Z]+\d+\s*-\s*[A-Z]$/.test(header) || // Format "AGL1 - O"
        value === "X" ||
        value === "x"
      ) {
        // Si la colonne est un code d'option (ex: "AGL1 - O") et la valeur est "X"
        if (value === "X" || value === "x") {
          mapped.options.push(header.trim());
        }
      }
    });

    return mapped;
  });
}
