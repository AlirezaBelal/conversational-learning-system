import gradio as gr
import spaces


# ZeroGPU requires at least one registered @spaces.GPU function during startup.
# This demo is CPU-only, so this no-op is intentionally never called.
@spaces.GPU(duration=10)
def _zerogpu_requirement():
    return None


ROUTES = {
    "/start": {
        "state": "start",
        "title": "Learner onboarding",
        "message": (
            "Welcome. Choose a route: course discovery, personalized learning path, "
            "or learner support."
        ),
        "next": ["/courses", "/interview", "/support", "/help"],
    },
    "/help": {
        "state": "help",
        "title": "Available routes",
        "message": (
            "Available commands: /courses for course discovery, /interview for "
            "the personalized-learning path, /support or /contact for learner support."
        ),
        "next": ["/courses", "/interview", "/support", "/contact"],
    },
    "/courses": {
        "state": "courses",
        "title": "Course discovery",
        "message": (
            "The learner is routed to the course-discovery experience. "
            "In the production system this route hands off to the dedicated learning flow."
        ),
        "next": ["/start", "/interview", "/support"],
    },
    "/interview": {
        "state": "interview",
        "title": "Personalized learning path",
        "message": (
            "The learner is routed to the personalized-path / interview entry point. "
            "This Space demonstrates routing only; it does not claim an LLM or autonomous tutor."
        ),
        "next": ["/start", "/courses", "/support"],
    },
    "/support": {
        "state": "support",
        "title": "Learner support",
        "message": (
            "The learner is routed to support. In the original system this is a dedicated "
            "support destination with explicit state handling."
        ),
        "next": ["/start", "/courses", "/interview"],
    },
    "/contact": {
        "state": "support",
        "title": "Learner support",
        "message": (
            "/contact is an alias for learner support and resolves to the same support state."
        ),
        "next": ["/start", "/courses", "/interview"],
    },
}


def normalize_command(command):
    text = (command or "").strip().lower()
    if not text:
        return "/start"

    if text.startswith("/start "):
        parameter = text.split(maxsplit=1)[1].strip()
        if parameter in {"courses", "interview", "support"}:
            return "/" + parameter

    if not text.startswith("/"):
        text = "/" + text

    return text


def route(command, current_state):
    normalized = normalize_command(command)
    data = ROUTES.get(normalized)

    if data is None:
        return (
            current_state or "unknown",
            "Unsupported command",
            (
                f"`{command}` is not a supported route. "
                "Return to /help to see available commands."
            ),
            "/help",
        )

    next_routes = " · ".join(data["next"])
    return data["state"], data["title"], data["message"], next_routes


with gr.Blocks(title="Conversational Learning System") as demo:
    gr.Markdown(
        "# 🎓 Conversational Learning System\n"
        "Interactive routing demo derived from the public Telegram learning gateway."
    )

    gr.Markdown(
        "> This demo does not connect to Telegram, store personal data, use a production database, "
        "or claim to contain an LLM/autonomous tutor."
    )

    state = gr.State("start")

    with gr.Row():
        command = gr.Dropdown(
            choices=[
                "/start",
                "/help",
                "/courses",
                "/interview",
                "/support",
                "/contact",
                "/start courses",
                "/start interview",
                "/start support",
            ],
            value="/start",
            allow_custom_value=True,
            label="Command / deep-link route",
        )
        route_btn = gr.Button("Route learner", variant="primary")

    with gr.Row():
        current_state = gr.Textbox(label="Resolved learner state", value="start", interactive=False)
        title = gr.Textbox(label="Flow", value="Learner onboarding", interactive=False)

    message = gr.Textbox(
        label="System response",
        value="Choose a command above and select Route learner.",
        lines=4,
        interactive=False,
    )
    next_routes = gr.Textbox(
        label="Suggested next routes",
        value="/courses · /interview · /support · /help",
        interactive=False,
    )

    def do_route(command_value, state_value):
        resolved_state, flow_title, response, next_values = route(command_value, state_value)
        return resolved_state, resolved_state, flow_title, response, next_values

    route_btn.click(
        do_route,
        inputs=[command, state],
        outputs=[state, current_state, title, message, next_routes],
    )

    gr.Markdown(
        "### Production architecture represented\n"
        "`Telegram user → Bot API / webhook → authentication → learner state → command routing → dedicated learning/support flow`\n\n"
        "The Hugging Face demo intentionally stops at the routing boundary."
    )


if __name__ == "__main__":
    demo.launch(ssr_mode=False)
