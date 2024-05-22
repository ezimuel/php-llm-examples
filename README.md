# Examples of using LLM in PHP

In this repository, I collected some PHP examples about the usage 
of Generative AI and Large Language Model (LLM) in PHP.

For the PHP code, I used [LLPhant](https://github.com/theodo-group/LLPhant) and [openai-php/client](https://github.com/openai-php/client) projects. 
For the LLM models I used [OpenAI](https://openai.com/) and [Llama 3](https://llama.meta.com/llama3/).
For semantic search, I used [Elasticsearch](https://github.com/elastic/elasticsearch)
as vector database.

## Configure the environment

To execute the examples you need to set some environment variables:

```bash
export OPENAI_API_KEY=xxx
export ELASTIC_URL=https://yyy
export ELASTIC_API_KEY=zzz
```

If you want to run Llama 3 locally you can install [Ollama](https://ollama.com/)
running the following command (in this case you don't need `OPENAI_API_KEY`):

```bash
ollama pull llama3
```

This will install Llama3 and the model will be available through HTTP API at
`http://localhost:11434/api/`.

If you want to interact with LLama 3 using a chat interface, you can execute
the following command:

```bash
ollama run llama3
```

## Examples

For OpenAI API example usage look at the following scripts:

- [openai_chat](src/openai_chat.php), a simple chat use case;
- [openai_image](src/openai_image.php), generate an image using `dall-e-3` model;
- [openai_speech](src/openai_speech.php), text-to-speech example using `tts-1` model;
- [openai_moderation](src/openai_moderation.php), moderation using `text-moderation-latest` model;
- [openai_function](src/openai_function.php), [function calling](https://platform.openai.com/docs/guides/function-calling) example;

For LLPhant examples you can see the following scripts:

- [llphant_chat](src/llphant_chat.php), a simple chat use case;
- [llphant_tool](src/llphant_tool.php), the function calling tool in LLPhant;

The Retrieval-Augmented Generation examples are in the [src/rag](src/rag/) folder.

I divided the folderusing different embedding models: [ELSER](https://www.elastic.co/guide/en/machine-learning/current/ml-nlp-elser.html),
[Llama3](https://llama.meta.com/llama3/) using ollama and GPT-3.5-turbo by [OpenAI](https://openai.com/).

For the RAG examples I used a simple PDF document that contains the [AI act](data/AI_act.pdf)
regulation proposed by the European Union in July 2023.
This document is not part of the knowledge of `GPT-3.5-turbo` that is fixed to 2022.
In the examples we store the document in the vector database (Elasticsearch) using the
`embedding.php` script, than we use the `qa.php` to ask for the question "What is the AI act?".
Using the RAG architecture we can expand the knowledge of the LLM, without fine-tuning the model,
providing also the sources (chunks) used to answer the question.

## Copyright

Copyright (C) 2024 by Enrico Zimuel


