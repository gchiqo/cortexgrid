# My Prompts — GTUH AI ცოდნის პლატფორმა

Every prompt I (the user) gave while building this project, in order.
Verbatim — original spelling kept. `[popup choice]` = a selection I made in an in-chat question.

---

## 1 — Discovery

**1.**
> https://aihackgtu.lovable.app/
> see image and content in that page  *(+ screenshot of the hackathon categories)*

**2.** `[popup choice]` → "Just summarize the page"

---

## 2 — The idea & feasibility

**3.**
> i have an idea
>
> i want to make universal system that will analize pdf-files and save its data in db. analze structured data in xlsx, csv, but they have to be formed acording to our template. also ability to import data with other forms (what you think is best API or something else). also i plan to make wordpress plugin that will analize sites content (mainly we will focus on woocomerce products, and site blogs, and added page content). logicly i plan to save and process them using those methods written in recomendations (RETRIEVAL / RAG — ჩანკინგი, ჰიბრიდული ძიება (BM25 + სემანტიკური), embeddings, reranking, vector DB; ეფექტურობა / ინფერენსი — KV cache, კვანტიზაცია (TurboQuant, QJL), caching; მონაცემები / BI) and have a chatbot that based on that information will answer questions and do other stuf to
>
> so our user experience will be like this — we have website where user can see what we do and registers; he has panel where he gets his api key, and can upload the resources he wants to be prcessed. he can see service usage and overal data that we have from his site/file/other_things and the AI configuration acording to his data and what he wants AI to be used for (user can create mutiple configurations for same data for example one for admin to help him add product and one for user to help him choose product (and more for example more specialied, for example if he has computer and hardware store AI configuration that will help user build their own pc so ai recomends what parts go well with each other))
>
> i plan to use postgressql
>
> what are your thoughts about this project does this complay with hakaton?

**4.**
> no i don't want to drop any of the parts of my plan. (wordpress plugin will be last so if we cant make it in time no problem) create plan.md and add that we will use those models(GROQ_API_KEY, GEMINI_API_KEY, ANTHROPIC_API_KEY)

---

## 3 — Stack & setup

**5.**
> ok wat statack should i use?
> recomend posibilityes

**6.** `[popup choice]` → "witch one is easyer for you to work in? (what about laravel + blade.php)"

**7.**
> ok i created laravel project in here
> crreate postgress things and what needs to be instaled install or if you cant give me prompts or guidence to do so

**8.**
> ok i did execute those *(ran the pgvector install + createdb + migrate)*
> use same keys as in there we may add google auth to as in that project
> /home/d/Desktop/code/waiaa/.env
> proceed with plan and go as far as you can without my interaction

---

## 4 — Filling the gaps (UI for configs & upload)

**9.**
> way do we have presets and where can i generate new configurations and where can i upload my files to sote them in db

---

## 5 — Userflow & the widget

**10.**
> this is how i imagine this thing working and userrflow
>
> user goes on our website see what we do (description cool animatins how data flows and stuf). useer registers and now he can upload files or connect or api to his site so we can take data he want us to have (he willl probably need cron or something like that to slowly sink data with us with api because basic api request cant take all data at one (confirm this yes/no and what ways is there)) based on the data we get we will generate probable ai chatbot configuration (one or many) (so user does not have to (but he still can)) then further specify things about configuring chatbot so we update configuration. and then he can copy info neded to integrate it with his site (url, key ...) (can be just embeded thing) so he can place it in his site wherewer he wants
>
> user can track usage for each chat and can see chat messages on our platform and other neded things (configure witch ai it uses and so on)

**11.** `[popup choice]` → "Embeddable widget + chat history (Recommended)"

**12.**
> i want to add ths to plan to "also user can test chat directl on our site. i imagine it like this split screen left and right on right side chat and on left side logs what happens and what is goin on on in our system."
> what would be best course of actions next

**13.** `ok`  *(→ glass-box console + conversational memory)*

**14.** `ok`  *(→ auto-generate agents from data)*

---

## 6 — Datasets architecture

**15.**
> user should be able to have multiple datasets (and 1 dataset can be constructed with multipe files (example user may import some of the data with multiple PDFs some of them with csv and some with api))
>
> for example 1. computer hardware store. 2. News portal 3. movies website 4. Online Travel Agency — on each of them be able to configure multiple ai chatbots
>
> 1. computer hardware store — a. user helper (find products, recommend pre-configured builds with names + urls); b. admin helper (add/improve product info, write description, suggest connected products, compatibility cpu+motherboard by socket)
> 2. news portal — a. user helper (search articles, summarize, what's today's news); b. admin helper (suggest improvements, more facts, link related articles)
> 3. movies website — a. user helper (search by interests / watched & liked); b. admin helper (link related movies)

**16.** `ok`  *(→ tool-execution / function-calling layer)*

---

## 7 — Reviewer feedback round (analisis.md)

**17.**
> see @analisis.md  *(a reviewer's critique)* — plus:
> at last when we are done: clear curent db things and generate files for examples 1. computer hardware store. 2. News portal 3. movies website 4. Online Travel Agency and i will upload them. when uploadin make cool animation that represents what is done to data. on http://127.0.0.1:8000/dashboard/console page add cooler animations on left side.
> and my questions: how can we improve this structure and things further? what may need improvement? what seems to be out of place? what can be added to improve userexperience? what other things users may wat?
> can you tell me how the process works i mean technical things when user kuploads file or conects to api how we store info and generate things — technical explanation.

**18.**
> build those (wordpress plugin not yet)   *(→ Knowledge Explorer, then landing page + feedback loop)*

---

## 8 — UX polish

**19.**
> on http://127.0.0.1:8000/dashboard/configs/suggest i may want to choose multiple so when i click "დამატება" dont just redirect me to another page
> add dark style and switcher (light/dark)

**20.**
> allow authorized user to see main page. and link "GTUH AI" logo to mainpage.
> on main page bellow curent page describe indepth what happens when file is uploaded (payload is sent) — programaticly and with animations. how ai agents are created. what happens when people write in chat.

**21.**
> continue — import example files and configure agents acording to this:
> 1. computer hardware store (user helper + admin helper)
> 2. news portal (user helper + admin helper)
> 3. movies website (user helper + admin helper)
> i will demonstrate Online Travel Agency mysqlf

---

## 9 — Bigger features (streaming, reranker, leads…)

**22.** *(analysis only)*
> how can we improve this structure and things further? what may need improvement? what seems to be out of place? what can be added to improve userexperience? what other things users may wat?
> do we use any of those: pgai, tsvector, graphql.
> don't change anything jsut suggest things

**23.**
> ok do — for a demo: streaming + clickable citations + widget customization. for a product: reranker (quality), source management/reprocess (trust), lead capture (business value).

---

## 10 — Header tweak, API docs, billing & plugin

**24.**
> ok. move themeswitcher in header.
> make api documentation guidelines page (switcher for diferent integration types), so other sites can automate things so they conect with us easily and data stays fresh.
> prepare for wordpress plugin and ask me questions about it if you have any?

**25.**
> update plugin so i can easily add embaded tings in setings
> add usage limit and allow to buy credits — by default set free tear unreasoneby big (so i wont run out of requests/tokens when showing and testing it). buy credits with flitt payment.
> refs: /home/d/Desktop/code/bms-s/app/Http/Controllers/Web/FlittPaymentController.php · /home/d/Desktop/code/waiaa/app/flitt.py
> test paymet variables: merchant_id 1549901, secret_key test

**26.**
> what's up 2 min is left   *(time crunch — just make it run)*

**27.**
> *(re-sent #25)* … what have you left from this

**28.**
> in http://127.0.0.1:8000/dashboard — API გასაღებები — button to copy key

---

## 11 — Demo prep & docs

**29.**
> don't touch or change anything — დამიწერე დემონსტრაციის ტექსტი და რა გავაკეთო თან როცა ვილაპარაკებ *(my 5-minute demo plan, step by step)*

**30.** *(my own rewritten demo narration, Georgian — I polished it)*

**31.**
> რა არის ემბედინგი და სად და როგორ ვიყენებთ

**32.**
> generate file where will be all of my prompts that i used for this project  *(← this file)*

---

*Built with Laravel + Postgres/pgvector + Groq · Gemini · Anthropic (Claude).*
