const Option = require("../models/Option");

async function getAllOptions(req, res) {
  try {
    const options = await Option.find();
    res.json({ ok: true, options });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

async function getOptionById(req, res) {
  try {
    const option = await Option.findById(req.params.id);
    if (!option) return res.status(404).json({ error: "Option not found" });
    res.json({ ok: true, option });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

async function createOption(req, res) {
  try {
    const { name } = req.body;

    if (!name) {
      return res.status(400).json({ error: "Missing option name" });
    }

    const existingOption = await Option.findOne({ name });
    if (existingOption) {
      return res.status(400).json({ error: "Option name already exists" });
    }

    const option = new Option({ name });
    await option.save();
    res.status(201).json({ ok: true, option: option.toObject() });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

async function updateOption(req, res) {
  try {
    const { id } = req.params;
    const { name } = req.body;

    const option = await Option.findById(id);
    if (!option) return res.status(404).json({ error: "Option not found" });

    if (name) {
      const existingOption = await Option.findOne({ name, _id: { $ne: id } });
      if (existingOption) {
        return res.status(400).json({ error: "Option name already exists" });
      }
      option.name = name;
    }

    await option.save();
    res.json({ ok: true, option: option.toObject() });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

async function deleteOption(req, res) {
  try {
    const { id } = req.params;
    const result = await Option.findByIdAndDelete(id);
    if (!result) return res.status(404).json({ error: "Option not found" });

    res.json({ ok: true });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

module.exports = {
  getAllOptions,
  getOptionById,
  createOption,
  updateOption,
  deleteOption,
};
