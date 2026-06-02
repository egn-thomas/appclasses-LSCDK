const Student = require("../models/Student");

// Récupérer tous les élèves
exports.getAllStudents = async (req, res) => {
  try {
    const students = await Student.find().sort({ createdAt: -1 });
    res.json({ ok: true, students });
  } catch (error) {
    console.error("Error fetching students:", error);
    res
      .status(500)
      .json({ error: "Erreur lors de la récupération des élèves" });
  }
};

// Récupérer un élève par ID
exports.getStudentById = async (req, res) => {
  try {
    const student = await Student.findById(req.params.id);
    if (!student) {
      return res.status(404).json({ error: "Élève non trouvé" });
    }
    res.json({ ok: true, student });
  } catch (error) {
    console.error("Error fetching student:", error);
    res
      .status(500)
      .json({ error: "Erreur lors de la récupération de l'élève" });
  }
};

// Créer un nouvel élève
exports.createStudent = async (req, res) => {
  try {
    const {
      className,
      firstName,
      lastName,
      gender,
      age,
      dateOfBirth,
      formation,
      options,
    } = req.body;

    // Validation basique - seulement les champs requis
    if (!className || !firstName || !lastName) {
      return res
        .status(400)
        .json({ error: "Classe, Prénom et Nom sont requis" });
    }

    const student = new Student({
      className,
      firstName,
      lastName,
      gender,
      age,
      dateOfBirth,
      formation,
      options: options || [],
    });

    await student.save();
    res.status(201).json({ ok: true, student });
  } catch (error) {
    console.error("Error creating student:", error);
    res.status(500).json({ error: "Erreur lors de la création de l'élève" });
  }
};

// Mettre à jour un élève
exports.updateStudent = async (req, res) => {
  try {
    const {
      className,
      firstName,
      lastName,
      gender,
      age,
      dateOfBirth,
      formation,
      options,
    } = req.body;

    const student = await Student.findById(req.params.id);
    if (!student) {
      return res.status(404).json({ error: "Élève non trouvé" });
    }

    // Mise à jour des champs
    if (className) student.className = className;
    if (firstName) student.firstName = firstName;
    if (lastName) student.lastName = lastName;
    if (gender) student.gender = gender;
    if (age) student.age = age;
    if (dateOfBirth) student.dateOfBirth = dateOfBirth;
    if (formation) student.formation = formation;
    if (options) student.options = options;

    await student.save();
    res.json({ ok: true, student });
  } catch (error) {
    console.error("Error updating student:", error);
    res.status(500).json({ error: "Erreur lors de la mise à jour de l'élève" });
  }
};

// Supprimer un élève
exports.deleteStudent = async (req, res) => {
  try {
    const student = await Student.findByIdAndDelete(req.params.id);
    if (!student) {
      return res.status(404).json({ error: "Élève non trouvé" });
    }
    res.json({ ok: true, message: "Élève supprimé avec succès" });
  } catch (error) {
    console.error("Error deleting student:", error);
    res.status(500).json({ error: "Erreur lors de la suppression de l'élève" });
  }
};
