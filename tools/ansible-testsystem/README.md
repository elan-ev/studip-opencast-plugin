# Ansible Stud.IP + Opencast Testsystem

These are ansible playbooks for creating a demo environment for Opencast and Stud.IP with activated Opencast plugin which is reset nightly.

## Caveats

- Certbot will be installed but you need to run it once by hand, afterwards a cronjob takes care of that (this might change in a future iteration)
