---
title: Conversational Learning System
emoji: 🎓
colorFrom: indigo
colorTo: purple
sdk: gradio
app_file: app.py
python_version: "3.12"
pinned: false
license: mit
---

# Conversational Learning System — Flow Demo

An interactive portfolio demo of the public Telegram learning gateway's routing logic.

The original project implements:
- Telegram webhook entry
- learner profile/state persistence
- course discovery routing
- personalized-path / interview routing
- learner support routing
- privacy-minimized interaction logging
- bounded retention policy
- webhook authentication

This Hugging Face Space intentionally demonstrates only the **product flow and routing semantics**.

It does **not**:
- connect to Telegram
- store personal learner data
- use a production database
- claim to contain an LLM
- claim to be an autonomous tutor
- represent a full LMS

Supported routes:
- `/start`
- `/help`
- `/courses`
- `/interview`
- `/support`
- `/contact`
- deep-link style `/start courses`, `/start interview`, `/start support`

Original project:
https://github.com/AlirezaBelal/conversational-learning-system
